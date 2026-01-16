<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Dtyq\AsyncEvent\Kernel\Annotation\Asynclist ener;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\ProjectFileConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Attachmentsprocess edEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMetadataDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Hyperf\Event\Annotation\list ener;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * Attachmentsprocess edEventEventlist ener - process project.jsData. */ #[Asynclist ener] #[list ener]

class Attachmentsprocess edEventSubscriber implements list enerInterface 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    private readonly ProjectMetadataDomainService $projectMetadataDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly LockerInterface $locker, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 /** * list en to events. * * @return array Array of event classes to listen to */ 
    public function listen(): array 
{
 return [ Attachmentsprocess edEvent::class, ]; 
}
 /** * process the event. * * @param object $event Event object */ 
    public function process(object $event): void 
{
 // Type check if (! $event instanceof Attachmentsprocess edEvent) 
{
 return; 
}
 $this->logger->info('Attachmentsprocess edEventSubscriber triggered', [ 'event_class' => get_class($event), 'parent_file_id' => $event->parentFileId, 'project_id' => $event->projectId, 'task_id' => $event->taskId, ]); // process project.js metadata for each attachment $this->processProjectMetadata($event); 
}
 /** * process project.js metadata from sibling files under parent directory. * * @param Attachmentsprocess edEvent $event Event object */ 
    private function processProjectMetadata(Attachmentsprocess edEvent $event): void 
{
 // Try to acquire parent directory level lock $lockKey = 'project_metadata_process_lock:' . $event->parentFileId; $lockowner = IdGenerator::getUniqueId32(); $lockExpireSeconds = 30; // Metadata processing timeout $lockAcquired = $this->acquireLock($lockKey, $lockowner , $lockExpireSeconds); if (! $lockAcquired) 
{
 $this->logger->info('Cannot acquire lock for parent directory metadata processing, skipping', [ 'parent_file_id' => $event->parentFileId, 'project_id' => $event->projectId, 'task_id' => $event->taskId, 'lock_key' => $lockKey, ]); return; // Skip processing if cannot acquire lock 
}
 $this->logger->info('Acquired lock for parent directory metadata processing', [ 'parent_file_id' => $event->parentFileId, 'project_id' => $event->projectId, 'task_id' => $event->taskId, 'lock_owner' => $lockowner , ]); try 
{
 $projectJsprocess ed = 0; $projectJsSkipped = 0; try 
{
 // Get sibling files under the parent directory $siblingFiles = $this->taskFileDomainService->getSiblingFileEntitiesByParentId( $event->parentFileId, $event->projectId ); $this->logger->info('Retrieved sibling files for metadata processing', [ 'parent_file_id' => $event->parentFileId, 'project_id' => $event->projectId, 'task_id' => $event->taskId, 'sibling_files_count' => count($siblingFiles), ]); foreach ($siblingFiles as $fileEntity) 
{
 // check if this is a project.js file if ($fileEntity->getFileName() === ProjectFileConstant::PROJECT_CONFIG_FILENAME) 
{
 try 
{
 $this->logger->info('Found project.js file, starting metadata processing', [ 'file_id' => $fileEntity->getFileId(), 'file_key' => $fileEntity->getFileKey(), 'task_id' => $event->taskId, ]); $this->projectMetadataDomainService->processProjectConfigFile($fileEntity); $this->logger->info('Successfully processed project.js metadata', [ 'file_id' => $fileEntity->getFileId(), 'task_id' => $event->taskId, ]); ++$projectJsprocess ed; 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to process project.js metadata', [ 'file_id' => $fileEntity->getFileId(), 'file_key' => $fileEntity->getFileKey(), 'task_id' => $event->taskId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); ++$projectJsSkipped; 
}
 
}
 
}
 if ($projectJsprocess ed > 0 || $projectJsSkipped > 0) 
{
 $this->logger->info('Project.js metadata processing completed', [ 'task_id' => $event->taskId, 'files_processed' => $projectJsprocess ed, 'files_skipped' => $projectJsSkipped, 'total_sibling_files' => count($siblingFiles), ]); 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to retrieve sibling files for metadata processing', [ 'parent_file_id' => $event->parentFileId, 'project_id' => $event->projectId, 'task_id' => $event->taskId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 finally 
{
 // Always release the lock if ($this->releaseLock($lockKey, $lockowner )) 
{
 $this->logger->info('Released lock for parent directory metadata processing', [ 'parent_file_id' => $event->parentFileId, 'lock_owner' => $lockowner , ]); 
}
 else 
{
 $this->logger->error('Failed to release lock for parent directory metadata processing', [ 'parent_file_id' => $event->parentFileId, 'lock_key' => $lockKey, 'lock_owner' => $lockowner , ]); 
}
 
}
 
}
 /** * Acquire distributed lock. */ 
    private function acquireLock(string $lockKey, string $lockowner , int $lockExpireSeconds): bool 
{
 return $this->locker->spinLock($lockKey, $lockowner , $lockExpireSeconds); 
}
 /** * Release distributed lock. */ 
    private function releaseLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->release($lockKey, $lockowner ); 
}
 
}
 
