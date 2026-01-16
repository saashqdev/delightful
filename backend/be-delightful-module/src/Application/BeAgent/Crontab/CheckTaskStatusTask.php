<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Crontab;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicTaskAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * check TimeRowStatusTask */ #[Crontab(rule: '12 * * * *', name: 'check TaskStatus', singleton: true, onOneServer: true, callback: 'execute', memo: '12check 6Incompletetopic including erStatus')] readonly

class check TaskStatusTask 
{
 
    private 
    const GLOBAL_LOCK_KEY = 'check_task_status_crontab_lock'; 
    private 
    const GLOBAL_LOCK_EXPIRE = 900; // Global lock timeout: 15 minutes 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected TopicAppService $topicAppService, 
    protected TopicTaskAppService $taskAppService, 
    protected SandboxInterface $sandboxService, 
    private LockerInterface $locker, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(self::class); 
}
 /** * execute Taskcheck 3Not updatedTaskAccording toSandbox statusUpdateTaskStatus */ 
    public function execute(): void 
{
 $enableCrontab = config('super-magic.task.check_task_crontab.enabled', false); if ($enableCrontab === false) 
{
 return; 
}
 $startTime = microtime(true); $globalLockowner = IdGenerator::getUniqueId32(); $this->logger->info('[check TaskStatusTask] Startcheck TimeNot updatedTask'); // Step 1: Acquire global lock to prevent multiple instances if (! $this->acquireGlobalLock($globalLockowner )) 
{
 $this->logger->info('[check TaskStatusTask] Cannot get global lock, other instance is executing task, skip this execution '); return; 
}
 try 
{
 // check TaskStatusincluding erStatus $this->checkTasksStatus(); $executionTime = round((microtime(true) - $startTime) * 1000, 2); $this->logger->info(sprintf( '[check TaskStatusTask] TaskExecution completed, time taken: %sms', $executionTime )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf('[check TaskStatusTask] Execution failed: %s', $e->getMessage()), [ 'exception' => $e, ]); 
}
 finally 
{
 // Step 2: Always release global lock $this->releaseGlobalLock($globalLockowner ); 
}
 
}
 /** * check TaskStatusincluding erStatus */ 
    private function checkTasksStatus(): void 
{
 try 
{
 // Get6Time $timeThreshold = date('Y-m-d H:i:s', strtotime('-3 hours')); // GetTimeouttopic list Update time7topic at most 100 $staleRunningTopics = $this->topicAppService->getTopicsExceedingUpdateTime($timeThreshold, 100); if (empty($staleRunningTopics)) 
{
 $this->logger->info('[check TaskStatusTask] Don't haveneed check Timeouttopic '); return; 
}
 $this->logger->info(sprintf('[check TaskStatusTask] Startcheck %d Timeouttopic including erStatus', count($staleRunningTopics))); $updatedToRunningCount = 0; $updatedToErrorCount = 0; foreach ($staleRunningTopics as $topic) 
{
 // each time loop Sleep0.1seconds Request usleep(100000); // 100000Microseconds = 0.1seconds $status = $this->taskAppService->updateTaskStatusFromSandbox($topic); if ($status === TaskStatus::RUNNING) 
{
 ++$updatedToRunningCount; continue; 
}
 ++$updatedToErrorCount; 
}
 $this->logger->info(sprintf( '[check TaskStatusTask] check complete Update %d topic as RowStatus%d topic as ErrorStatus', $updatedToRunningCount, $updatedToErrorCount )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf('[check TaskStatusTask] check TaskStatusFailed: %s', $e->getMessage())); throw $e; 
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
 $this->logger->info('[check TaskStatusTask] GlobalLockReleaseSuccess'); 
}
 else 
{
 $this->logger->error('[check TaskStatusTask] Global lock release failed, may need manual check'); 
}
 
}
 
}
 
