<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Contact\Repository\Persistence\Magicuser Repository;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use Dtyq\AsyncEvent\Kernel\Annotation\Asynclist ener;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Directorydelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileBatchMoveEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileContentSavedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Filedelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileMovedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileRenamedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FilesBatchdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileUploadedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TaskFileItemDTO;
use Hyperf\Event\Annotation\list ener;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * File change notification subscriber. * list en to all file change events and push WebSocket notifications to clients. * Using async listener to avoid blocking the main business process. */ #[Asynclist ener] #[list ener]

class FileChangeNotificationSubscriber implements list enerInterface 
{
 
    private readonly LoggerInterface $logger; 
    public function __construct( 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly Magicuser Repository $magicuser Repository, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(self::class); 
}
 /** * list en to all file change events. */ 
    public function listen(): array 
{
 return [ FileUploadedEvent::class, Filedelete dEvent::class, Directorydelete dEvent::class, FilesBatchdelete dEvent::class, FileRenamedEvent::class, FileMovedEvent::class, FileBatchMoveEvent::class, FileContentSavedEvent::class, ]; 
}
 /** * process file change events and push notifications. */ 
    public function process(object $event): void 
{
 try 
{
 match (true) 
{
 $event instanceof FileUploadedEvent => $this->handleFileUploaded($event), $event instanceof Filedelete dEvent => $this->handleFiledelete d($event), $event instanceof Directorydelete dEvent => $this->handleDirectorydelete d($event), $event instanceof FilesBatchdelete dEvent => $this->handleBatchdelete d($event), $event instanceof FileRenamedEvent => $this->handleFileRenamed($event), $event instanceof FileMovedEvent => $this->handleFileMoved($event), $event instanceof FileBatchMoveEvent => $this->handleBatchMoved($event), $event instanceof FileContentSavedEvent => $this->handleFileContentSaved($event), default => null, 
}
; 
}
 catch (Throwable $e) 
{
 // Log error but don't throw to avoid affecting main business logic $this->logger->error('File change notification failed', [ 'event' => get_class($event), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 /** * Handle file uploaded event. */ 
    private function handleFileUploaded(FileUploadedEvent $event): void 
{
 $fileEntity = $event->getFileEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 $this->logger->warning('Project not found for file upload notification', [ 'project_id' => $fileEntity->getProjectId(), ]); return; 
}
 $pushData = $this->buildPushData( operation: 'add', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $event->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($event->getuser Id(), $pushData); 
}
 /** * Handle file deleted event. */ 
    private function handleFiledelete d(Filedelete dEvent $event): void 
{
 $fileEntity = $event->getFileEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 return; 
}
 $pushData = $this->buildPushData( operation: 'delete', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $event->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($event->getuser Id(), $pushData); 
}
 /** * Handle file content saved event. */ 
    private function handleFileContentSaved(FileContentSavedEvent $event): void 
{
 $fileEntity = $event->getFileEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 $this->logger->warning('Project not found for file content saved notification', [ 'project_id' => $fileEntity->getProjectId(), ]); return; 
}
 $pushData = $this->buildPushData( operation: 'update', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $event->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($event->getuser Id(), $pushData); 
}
 /** * Handle directory deleted event. */ 
    private function handleDirectorydelete d(Directorydelete dEvent $event): void 
{
 $fileEntity = $event->getDirectoryEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 return; 
}
 $userAuthorization = $event->getuser Authorization(); $pushData = $this->buildPushData( operation: 'delete', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $userAuthorization->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($userAuthorization->getId(), $pushData); 
}
 /** * Handle batch deleted event. */ 
    private function handleBatchdelete d(FilesBatchdelete dEvent $event): void 
{
 $projectId = $event->getProjectId(); $fileIds = $event->getFileIds(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($projectId); if (! $projectEntity) 
{
 return; 
}
 $userAuthorization = $event->getuser Authorization(); // Build batch changes $changes = []; foreach ($fileIds as $fileId) 
{
 $changes[] = [ 'operation' => 'delete', 'file_id' => (string) $fileId, ]; 
}
 $pushData = $this->buildBatchPushData( projectId: (string) $projectId, workspaceId: $projectEntity->getWorkDir(), changes: $changes, organizationCode: $userAuthorization->getOrganizationCode(), topicId: '' ); $this->pushNotification($userAuthorization->getId(), $pushData); 
}
 /** * Handle file renamed event. */ 
    private function handleFileRenamed(FileRenamedEvent $event): void 
{
 $fileEntity = $event->getFileEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 return; 
}
 $userAuthorization = $event->getuser Authorization(); $pushData = $this->buildPushData( operation: 'update', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $userAuthorization->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($userAuthorization->getId(), $pushData); 
}
 /** * Handle file moved event. */ 
    private function handleFileMoved(FileMovedEvent $event): void 
{
 $fileEntity = $event->getFileEntity(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 return; 
}
 $userAuthorization = $event->getuser Authorization(); $pushData = $this->buildPushData( operation: 'update', projectId: (string) $fileEntity->getProjectId(), workspaceId: $projectEntity->getWorkDir(), fileEntity: $fileEntity, workDir: $projectEntity->getWorkDir(), organizationCode: $userAuthorization->getOrganizationCode(), conversationId: '', topicId: (string) $fileEntity->getTopicId() ); $this->pushNotification($userAuthorization->getId(), $pushData); 
}
 /** * Handle batch moved event. */ 
    private function handleBatchMoved(FileBatchMoveEvent $event): void 
{
 $projectId = $event->getProjectId(); $fileIds = $event->getFileIds(); $projectEntity = $this->projectDomainService->getProjectNotuser Id($projectId); if (! $projectEntity) 
{
 return; 
}
 // Build batch changes $changes = []; $topicId = ''; foreach ($fileIds as $fileId) 
{
 try 
{
 $fileEntity = $this->taskFileDomainService->getById($fileId); if ($fileEntity) 
{
 $fileDto = TaskFileItemDTO::fromEntity($fileEntity, $projectEntity->getWorkDir()); $changes[] = [ 'operation' => 'update', 'file_id' => (string) $fileId, 'file' => $fileDto->toArray(), ]; // Use the first file's topicId if available if (empty($topicId) && $fileEntity->getTopicId() > 0) 
{
 $topicId = (string) $fileEntity->getTopicId(); 
}
 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->warning('Failed to get file info for batch move notification', [ 'file_id' => $fileId, 'error' => $e->getMessage(), ]); 
}
 
}
 if (empty($changes)) 
{
 return; 
}
 $pushData = $this->buildBatchPushData( projectId: (string) $projectId, workspaceId: $projectEntity->getWorkDir(), changes: $changes, organizationCode: $event->getOrganizationCode(), topicId: $topicId ); $this->pushNotification($event->getuser Id(), $pushData); 
}
 /** * Build push data structure for single file operation. * @param mixed $fileEntity */ 
    private function buildPushData( string $operation, string $projectId, string $workspaceId, $fileEntity, string $workDir, string $organizationCode = '', string $conversationId = '', string $topicId = '' ): array 
{
 $fileDto = TaskFileItemDTO::fromEntity($fileEntity, $workDir); $changes = [ [ 'operation' => $operation, 'file_id' => (string) $fileEntity->getFileId(), 'file' => $fileDto->toArray(), ], ]; return $this->buildBatchPushData( projectId: $projectId, workspaceId: $workspaceId, changes: $changes, organizationCode: $organizationCode, conversationId: $conversationId, topicId: $topicId ); 
}
 /** * Build batch push data structure. */ 
    private function buildBatchPushData( string $projectId, string $workspaceId, array $changes, string $organizationCode = '', string $conversationId = '', string $topicId = '' ): array 
{
 return [ 'type' => 'seq', 'seq' => [ 'magic_id' => '', 'seq_id' => '', 'message_id' => '', 'refer_message_id' => '', 'sender_message_id' => '', 'conversation_id' => $conversationId, 'organization_code' => $organizationCode, 'message' => [ 'type' => 'super_magic_file_change', 'project_id' => $projectId, 'workspace_id' => $workspaceId, 'topic_id' => $topicId, 'changes' => $changes, 'timestamp' => date('c'), ], ], ]; 
}
 /** * Push notification via WebSocket. */ 
    private function pushNotification(string $userId, array $pushData): void 
{
 // Get user's magicId from userId $magicId = $this->getMagicIdByuser Id($userId); if (empty($magicId)) 
{
 $this->logger->warning('Cannot get magicId for user', ['user_id' => $userId]); return; 
}
 $message = $pushData['seq']['message'] ?? []; $this->logger->info('Pushing file change notification', [ 'magic_id' => $magicId, 'project_id' => $message['project_id'] ?? '', 'changes_count' => count($message['changes'] ?? []), ]); // Push via WebSocket SocketIOUtil::sendIntermediate( SocketEventType::Intermediate, $magicId, $pushData ); 
}
 /** * Get magicId by userId. */ 
    private function getMagicIdByuser Id(string $userId): string 
{
 try 
{
 $userEntity = $this->magicuser Repository->getuser ById($userId); if ($userEntity) 
{
 return (string) $userEntity->getMagicId(); 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to get magicId', [ 'user_id' => $userId, 'error' => $e->getMessage(), ]); 
}
 return ''; 
}
 
}
 
