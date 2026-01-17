<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Crontab;

use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Carbon\Carbon;
use Delightful\BeDelightful\Application\BeAgent\Service\HandleAgentMessageAppService;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Message Compensation Crontab
 * Message compensation scheduled task - handles potentially missed message queue compensation
 */
#[Crontab(
    rule: '*/5 * * * * *',                    // Execute every 5 seconds
    name: 'MessageCompensationCrontab',
    singleton: true,                          // Singleton mode to prevent duplicate execution
    mutexExpires: 60,                         // Mutex lock expires in 60 seconds
    onOneServer: true,                        // Execute on only one server
    callback: 'execute',
    memo: 'Message compensation scheduled task for handling missed messages',
    enable: false
)]
readonly class MessageCompensationCrontab
{
    private const GLOBAL_LOCK_KEY = 'message_compensation_crontab_lock';

    // Unified topic lock prefix - consistent with all message queue services
    private const TOPIC_LOCK_PREFIX = 'msg_queue_compensation:topic:';

    private const GLOBAL_LOCK_EXPIRE = 60; // Global lock timeout: 60 seconds

    private const TOPIC_LOCK_EXPIRE = 20;  // Topic lock timeout: 20 seconds (same as consumer)

    private const TIME_WINDOW_MINUTES = 20; // Query messages from last 20 minutes

    private const MAX_TOPICS_PER_BATCH = 50; // Maximum topics to process per batch

    private const MAX_TOPICS_PER_EXECUTION = 20; // Maximum topics to process per execution

    protected LoggerInterface $logger;

    public function __construct(
        private HandleAgentMessageAppService $handleAgentMessageAppService,
        private LockerInterface $locker,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(self::class);
    }

    /**
     * Main execution method.
     */
    public function execute(): void
    {
        $enableCrontab = config('be-delightful.message.enable_compensate', false);
        if ($enableCrontab === false) {
            return;
        }
        $startTime = microtime(true);
        $globalLockOwner = IdGenerator::getUniqueId32();

        $this->logger->info('Message compensation scheduled task started');

        // Step 1: Acquire global lock to prevent multiple instances
        if (! $this->acquireGlobalLock($globalLockOwner)) {
            $this->logger->info('Unable to acquire global lock, another instance is executing compensation task, skipping this execution');
            return;
        }

        try {
            // Step 2: Get topics that need compensation
            $topicTasks = $this->getCompensationTopics();

            if (empty($topicTasks)) {
                $this->logger->info('No topics need compensation processing');
                return;
            }

            $topicCount = count($topicTasks);
            $this->logger->info(sprintf('Found %d topics that need compensation processing', $topicCount));

            // Step 3: Process topics concurrently in batches
            $this->processTopicsConcurrently($topicTasks);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info(sprintf(
                'Message compensation task completed, topics processed: %d, time elapsed: %sms',
                $topicCount,
                $executionTime
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Message compensation task execution exception: %s',
                $e->getMessage()
            ), ['exception' => $e]);
        } finally {
            // Step 4: Always release global lock
            $this->releaseGlobalLock($globalLockOwner);
        }
    }

    /**
     * Get topics that need compensation processing
     * Get list of topics that need compensation processing (high-performance query).
     */
    private function getCompensationTopics(): array
    {
        try {
            $timeThreshold = Carbon::now()->subMinutes(self::TIME_WINDOW_MINUTES);

            // High-performance query: Use simple conditions and proper indexing
            // Use native SQL for better performance, avoid ORM overhead, query only topic_id for grouping
            $sql = '
                SELECT DISTINCT topic_id 
                FROM ' . (new TaskMessageModel())->getTable() . " 
                WHERE processing_status IN (?, ?) 
                  AND created_at >= ? 
                  AND sender_type = 'assistant'
                GROUP BY topic_id
                ORDER BY topic_id ASC 
                LIMIT ?
            ";

            $results = Db::select($sql, [
                TaskMessageEntity::PROCESSING_STATUS_PENDING,
                TaskMessageEntity::PROCESSING_STATUS_PROCESSING,
                $timeThreshold->toDateTimeString(),
                self::MAX_TOPICS_PER_BATCH,
            ]);

            $topicTasks = [];
            foreach ($results as $result) {
                // Convert stdClass to array for consistent access
                $resultArray = (array) $result;
                $topicTasks[] = [
                    'topic_id' => (int) $resultArray['topic_id'],
                    'task_id' => 0, // Don't need specific task_id, use 0 uniformly
                ];
            }

            $this->logger->info(sprintf(
                'Queried %d topics that need compensation, time window: %s',
                count($topicTasks),
                $timeThreshold->toDateTimeString()
            ));

            return $topicTasks;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Query compensation topics failed: %s',
                $e->getMessage()
            ), ['exception' => $e]);
            return [];
        }
    }

    /**
     * Process topics concurrently using coroutines
     * Use coroutines to process topic list concurrently.
     */
    private function processTopicsConcurrently(array $topicTasks): void
    {
        $batches = array_chunk($topicTasks, self::MAX_TOPICS_PER_EXECUTION);
        $totalProcessed = 0;
        $totalSuccessful = 0;

        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info(sprintf(
                'Starting coroutine concurrent processing of batch %d topics, count: %d',
                $batchIndex + 1,
                count($batch)
            ));

            // Create parallel processor for this batch
            $parallel = new Parallel();
            $fromCoroutineId = Coroutine::id();

            // Add coroutine tasks for each topic in this batch
            foreach ($batch as $topicTask) {
                $parallel->add(function () use ($topicTask, $fromCoroutineId) {
                    // Copy coroutine context to maintain proper isolation
                    CoContext::copy($fromCoroutineId);

                    return $this->processTopicWithLock(
                        $topicTask['topic_id'],
                        0  // Compensation task doesn't need specific task_id
                    );
                });
            }

            // Wait for all coroutines in this batch to complete
            $results = $parallel->wait();

            // Collect statistics
            $batchSuccessful = 0;
            foreach ($results as $result) {
                ++$totalProcessed;
                if ($result['success']) {
                    ++$totalSuccessful;
                    ++$batchSuccessful;
                }
            }

            $this->logger->info(sprintf(
                'Batch %d topic coroutine processing completed, successful: %d, total: %d',
                $batchIndex + 1,
                $batchSuccessful,
                count($results)
            ));
        }

        $this->logger->info(sprintf(
            'All topic coroutine processing completed, total: %d, successful: %d, failed: %d',
            $totalProcessed,
            $totalSuccessful,
            $totalProcessed - $totalSuccessful
        ));
    }

    /**
     * Process single topic with lock protection
     * Use lock protection to process single topic.
     */
    private function processTopicWithLock(int $topicId, int $taskId): array
    {
        $lockKey = self::TOPIC_LOCK_PREFIX . $topicId;
        $lockOwner = IdGenerator::getUniqueId32();
        $startTime = microtime(true);

        // Try to acquire topic lock
        if (! $this->acquireTopicLock($lockKey, $lockOwner)) {
            $this->logger->info(sprintf(
                'topic %d is being processed by another instance, skipping compensation',
                $topicId
            ));
            return [
                'success' => false,
                'topic_id' => $topicId,
                'reason' => 'lock_failed',
                'processed_count' => 0,
            ];
        }

        try {
            $this->logger->info(sprintf(
                'Starting compensation processing for topic %d, lock owner: %s',
                $topicId,
                $lockOwner
            ));

            // Call the batch processing method
            $processedCount = $this->handleAgentMessageAppService->batchHandleAgentMessage($topicId, 0);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info(sprintf(
                'topic %d compensation processing completed, messages processed: %d, time elapsed: %sms',
                $topicId,
                $processedCount,
                $executionTime
            ));

            return [
                'success' => true,
                'topic_id' => $topicId,
                'processed_count' => $processedCount,
                'execution_time_ms' => $executionTime,
            ];
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'topic %d compensation processing failed: %s',
                $topicId,
                $e->getMessage()
            ), [
                'topic_id' => $topicId,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'topic_id' => $topicId,
                'reason' => 'processing_failed',
                'error' => $e->getMessage(),
                'processed_count' => 0,
            ];
        } finally {
            // Always release topic lock
            if ($this->releaseTopicLock($lockKey, $lockOwner)) {
                $this->logger->info(sprintf(
                    'Released lock for topic %d, owner: %s',
                    $topicId,
                    $lockOwner
                ));
            } else {
                $this->logger->error(sprintf(
                    'Failed to release lock for topic %d, owner: %s, may need manual intervention',
                    $topicId,
                    $lockOwner
                ));
            }
        }
    }

    /**
     * Acquire global lock.
     */
    private function acquireGlobalLock(string $lockOwner): bool
    {
        return $this->locker->mutexLock(self::GLOBAL_LOCK_KEY, $lockOwner, self::GLOBAL_LOCK_EXPIRE);
    }

    /**
     * Release global lock.
     */
    private function releaseGlobalLock(string $lockOwner): void
    {
        if ($this->locker->release(self::GLOBAL_LOCK_KEY, $lockOwner)) {
            $this->logger->info('Global lock released successfully');
        } else {
            $this->logger->error('Failed to release global lock, may need manual check');
        }
    }

    /**
     * Acquire topic lock.
     */
    private function acquireTopicLock(string $lockKey, string $lockOwner): bool
    {
        return $this->locker->mutexLock($lockKey, $lockOwner, self::TOPIC_LOCK_EXPIRE);
    }

    /**
     * Release topic lock.
     */
    private function releaseTopicLock(string $lockKey, string $lockOwner): bool
    {
        return $this->locker->release($lockKey, $lockOwner);
    }
}
