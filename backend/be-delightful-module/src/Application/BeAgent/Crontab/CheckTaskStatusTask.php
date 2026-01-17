<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Crontab;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\BeAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\BeAgent\Service\TopicTaskAppService;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Check tasks that have been in running state for a long time
 */
#[Crontab(rule: '12 * * * *', name: 'CheckTaskStatus', singleton: true, onOneServer: true, callback: 'execute', memo: 'Check topics and container status not completed for over 6 hours at the 12th minute of every hour')]
readonly class CheckTaskStatusTask
{
    private const GLOBAL_LOCK_KEY = 'check_task_status_crontab_lock';

    private const GLOBAL_LOCK_EXPIRE = 900; // Global lock timeout: 15 minutes

    protected LoggerInterface $logger;

    public function __construct(
        protected TopicAppService $topicAppService,
        protected TopicTaskAppService $taskAppService,
        protected SandboxInterface $sandboxService,
        private LockerInterface $locker,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(self::class);
    }

    /**
     * Execute task, check tasks not updated for over 3 hours and update task status based on sandbox status
     */
    public function execute(): void
    {
        $enableCrontab = config('be-delightful.task.check_task_crontab.enabled', false);
        if ($enableCrontab === false) {
            return;
        }

        $startTime = microtime(true);
        $globalLockOwner = IdGenerator::getUniqueId32();

        $this->logger->info('[CheckTaskStatusTask] Starting to check tasks not updated for a long time');

        // Step 1: Acquire global lock to prevent multiple instances
        if (! $this->acquireGlobalLock($globalLockOwner)) {
            $this->logger->info('[CheckTaskStatusTask] Unable to acquire global lock, another instance is executing task, skipping this execution');
            return;
        }

        try {
            // Check task status and container status
            $this->checkTasksStatus();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info(sprintf(
                '[CheckTaskStatusTask] Task execution completed, time elapsed: %sms',
                $executionTime
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf('[CheckTaskStatusTask] Execution failed: %s', $e->getMessage()), [
                'exception' => $e,
            ]);
        } finally {
            // Step 2: Always release global lock
            $this->releaseGlobalLock($globalLockOwner);
        }
    }

    /**
     * Check task status and container status
     */
    private function checkTasksStatus(): void
    {
        try {
            // Get time point 6 hours ago
            $timeThreshold = date('Y-m-d H:i:s', strtotime('-3 hours'));

            // Get timeout topic list (topics with update time over 7 hours, max 100)
            $staleRunningTopics = $this->topicAppService->getTopicsExceedingUpdateTime($timeThreshold, 100);

            if (empty($staleRunningTopics)) {
                $this->logger->info('[CheckTaskStatusTask] No timeout topics to check');
                return;
            }

            $this->logger->info(sprintf('[CheckTaskStatusTask] Starting to check container status of %d timeout topics', count($staleRunningTopics)));

            $updatedToRunningCount = 0;
            $updatedToErrorCount = 0;

            foreach ($staleRunningTopics as $topic) {
                // Sleep 0.1 seconds after each loop to avoid too frequent requests
                usleep(100000); // 100000 microseconds = 0.1 seconds
                $status = $this->taskAppService->updateTaskStatusFromSandbox($topic);
                if ($status === TaskStatus::RUNNING) {
                    ++$updatedToRunningCount;
                    continue;
                }
                ++$updatedToErrorCount;
            }
            $this->logger->info(sprintf(
                '[CheckTaskStatusTask] Check completed, updated %d topics to running status, %d topics to error status',
                $updatedToRunningCount,
                $updatedToErrorCount
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf('[CheckTaskStatusTask] Check task status failed: %s', $e->getMessage()));
            throw $e;
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
            $this->logger->info('[CheckTaskStatusTask] Global lock released successfully');
        } else {
            $this->logger->error('[CheckTaskStatusTask] Failed to release global lock, may need manual check');
        }
    }
}
