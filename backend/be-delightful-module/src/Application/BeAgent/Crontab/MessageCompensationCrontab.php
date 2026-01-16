<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Crontab;

use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Carbon\Carbon;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleAgentMessageAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * Message Compensation Crontab * Messagecompensation ScheduledTask - process MessageQueuecompensation */ #[Crontab( rule: '*/5 * * * * *', // execute every 5 seconds name: 'MessageCompensationCrontab', singleton: true, // Singleton mode to prevent duplicate execution mutexExpires: 60, // Mutex lock expires in 60 seconds onOneServer: true, // execute on only one server callback: 'execute', memo: 'Message compensation scheduled task for handling missed messages', enable: false )] readonly

class MessageCompensationCrontab 
{
 
    private 
    const GLOBAL_LOCK_KEY = 'message_compensation_crontab_lock'; // Unified topic lock prefix - consistent with all message queue services 
    private 
    const TOPIC_LOCK_PREFIX = 'msg_queue_compensation:topic:'; 
    private 
    const GLOBAL_LOCK_EXPIRE = 60; // Global lock timeout: 60 seconds 
    private 
    const TOPIC_LOCK_EXPIRE = 20; // Topic lock timeout: 20 seconds (same as consumer) 
    private 
    const TIME_WINDOW_MINUTES = 20; // query messages from last 20 minutes 
    private 
    const MAX_TOPICS_PER_BATCH = 50; // Maximum topics to process per batch 
    private 
    const MAX_TOPICS_PER_EXECUTION = 20; // Maximum topics to process per execution 
    protected LoggerInterface $logger; 
    public function __construct( 
    private HandleAgentMessageAppService $handleAgentMessageAppService, 
    private LockerInterface $locker, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(self::class); 
}
 /** * Main execution method. */ 
    public function execute(): void 
{
 $enableCrontab = config('super-magic.message.enable_compensate', false); if ($enableCrontab === false) 
{
 return; 
}
 $startTime = microtime(true); $globalLockowner = IdGenerator::getUniqueId32(); $this->logger->info('Messagecompensation ScheduledTaskStartexecute '); // Step 1: Acquire global lock to prevent multiple instances if (! $this->acquireGlobalLock($globalLockowner )) 
{
 $this->logger->info('Cannot get global lock, other instance is executing compensation task, skip this execution '); return; 
}
 try 
{
 // Step 2: Get topics that need compensation $topicTasks = $this->getCompensationTopics(); if (empty($topicTasks)) 
{
 $this->logger->info('Don't haveneed compensation process topic '); return; 
}
 $topicCount = count($topicTasks); $this->logger->info(sprintf('Found %d topics need compensation processing ', $topicCount)); // Step 3: process topics concurrently in batches $this->processTopicsConcurrently($topicTasks); $executionTime = round((microtime(true) - $startTime) * 1000, 2); $this->logger->info(sprintf( 'Messagecompensation ScheduledTaskexecute complete process topic : %dtime taken : %sms', $topicCount, $executionTime )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'Messagecompensation ScheduledTaskexecute Exception: %s', $e->getMessage() ), ['exception' => $e]); 
}
 finally 
{
 // Step 4: Always release global lock $this->releaseGlobalLock($globalLockowner ); 
}
 
}
 /** * Get topics that need compensation processing * Getneed compensation process topic list query . */ 
    private function getCompensationTopics(): array 
{
 try 
{
 $timeThreshold = Carbon::now()->subMinutes(self::TIME_WINDOW_MINUTES); // High-performance query: Use simple conditions and proper indexing // UsingSQLORMquery topic_idRowGroup $sql = ' SELECT DISTINCT topic_id FROM ' . (new TaskMessageModel())->gettable () . WHERE processing_status IN (?, ?) AND created_at >= ? AND sender_type = 'assistant' GROUP BY topic_id ORDER BY topic_id ASC LIMIT ? ; $results = Db::select($sql, [ TaskMessageEntity::PROCESSING_STATUS_PENDING, TaskMessageEntity::PROCESSING_STATUS_PROCESSING, $timeThreshold->toDateTimeString(), self::MAX_TOPICS_PER_BATCH, ]); $topicTasks = []; foreach ($results as $result) 
{
 // Convert stdClass to array for consistent access $resultArray = (array) $result; $topicTasks[] = [ 'topic_id' => (int) $resultArray['topic_id'], 'task_id' => 0, // not needed Concretetask_idUsing0 ]; 
}
 $this->logger->info(sprintf( 'query %d need compensation topic Time: %s', count($topicTasks), $timeThreshold->toDateTimeString() )); return $topicTasks; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'query compensation topic Failed: %s', $e->getMessage() ), ['exception' => $e]); return []; 
}
 
}
 /** * process topics concurrently using coroutines * UsingCoroutineConcurrencyprocess topic list . */ 
    private function processTopicsConcurrently(array $topicTasks): void 
{
 $batches = array_chunk($topicTasks, self::MAX_TOPICS_PER_EXECUTION); $totalprocess ed = 0; $totalSuccessful = 0; foreach ($batches as $batchIndex => $batch) 
{
 $this->logger->info(sprintf( 'StartCoroutineConcurrencyprocess %d topic Quantity: %d', $batchIndex + 1, count($batch) )); // Create parallel processor for this batch $parallel = new Parallel(); $fromCoroutineId = Coroutine::id(); // Add coroutine tasks for each topic in this batch foreach ($batch as $topicTask) 
{
 $parallel->add(function () use ($topicTask, $fromCoroutineId) 
{
 // Copy coroutine context to maintain proper isolation CoContext::copy($fromCoroutineId);
return $this->processTopicWithLock( $topicTask['topic_id'], 0 // compensation Tasknot needed Concretetask_id ); 
}
); 
}
 // Wait for all coroutines in this batch to complete $results = $parallel->wait(); // Collect statistics $batchSuccessful = 0; foreach ($results as $result) 
{
 ++$totalprocess ed; if ($result['success']) 
{
 ++$totalSuccessful; ++$batchSuccessful; 
}
 
}
 $this->logger->info(sprintf( ' %d topic Coroutineprocess complete Success: %d: %d', $batchIndex + 1, $batchSuccessful, count($results) )); 
}
 $this->logger->info(sprintf( 'Alltopic Coroutineprocess complete Total: %dSuccess: %dFailed: %d', $totalprocess ed, $totalSuccessful, $totalprocess ed - $totalSuccessful )); 
}
 /** * process single topic with lock protection * UsingLockProtectedprocess Singletopic . */ 
    private function processTopicWithLock(int $topicId, int $taskId): array 
{
 $lockKey = self::TOPIC_LOCK_PREFIX . $topicId; $lockowner = IdGenerator::getUniqueId32(); $startTime = microtime(true); // Try to acquire topic lock if (! $this->acquireTopicLock($lockKey, $lockowner )) 
{
 $this->logger->info(sprintf( 'topic %d AtInstanceprocess Skipcompensation process ', $topicId )); return [ 'success' => false, 'topic_id' => $topicId, 'reason' => 'lock_failed', 'processed_count' => 0, ]; 
}
 try 
{
 $this->logger->info(sprintf( 'Startcompensation process topic %dLockHave: %s', $topicId, $lockowner )); // Call the batch processing method $processedCount = $this->handleAgentMessageAppService->batchHandleAgentMessage($topicId, 0); $executionTime = round((microtime(true) - $startTime) * 1000, 2); $this->logger->info(sprintf( 'topic %d compensation process complete process Message: %dtime taken : %sms', $topicId, $processedCount, $executionTime )); return [ 'success' => true, 'topic_id' => $topicId, 'processed_count' => $processedCount, 'execution_time_ms' => $executionTime, ]; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'topic %d compensation process ing failed: %s', $topicId, $e->getMessage() ), [ 'topic_id' => $topicId, 'exception' => $e, ]); return [ 'success' => false, 'topic_id' => $topicId, 'reason' => 'processing_failed', 'error' => $e->getMessage(), 'processed_count' => 0, ]; 
}
 finally 
{
 // Always release topic lock if ($this->releaseTopicLock($lockKey, $lockowner )) 
{
 $this->logger->info(sprintf( 'Release topic %d LockHave: %s', $topicId, $lockowner )); 
}
 else 
{
 $this->logger->error(sprintf( 'Release topic %d LockFailedHave: %sneed ', $topicId, $lockowner )); 
}
 
}
 
}
 /** * Acquire global lock. */ 
    private function acquireGlobalLock(string $lockowner ): bool 
{
 return $this->locker->mutexLock(self::GLOBAL_LOCK_KEY, $lockowner , self::GLOBAL_LOCK_EXPIRE); 
}
 /** * Release global lock. */ 
    private function releaseGlobalLock(string $lockowner ): void 
{
 if ($this->locker->release(self::GLOBAL_LOCK_KEY, $lockowner )) 
{
 $this->logger->info('GlobalLockReleaseSuccess'); 
}
 else 
{
 $this->logger->error('Global lock release failed, may need manual check'); 
}
 
}
 /** * Acquire topic lock. */ 
    private function acquireTopicLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->mutexLock($lockKey, $lockowner , self::TOPIC_LOCK_EXPIRE); 
}
 /** * Release topic lock. */ 
    private function releaseTopicLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->release($lockKey, $lockowner ); 
}
 
}
 
