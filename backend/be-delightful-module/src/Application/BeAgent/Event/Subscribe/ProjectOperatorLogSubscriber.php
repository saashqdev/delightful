<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use Dtyq\AsyncEvent\Kernel\Annotation\Asynclist ener;
use Delightful\BeDelightful\Domain\SuperAgent\Constants\OperationAction;
use Delightful\BeDelightful\Domain\SuperAgent\Constants\ResourceType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectOperationLogEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Directorydelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileBatchMoveEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileContentSavedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Filedelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileMovedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileRenamedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileReplacedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FilesBatchdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\FileUploadedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectCreatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Projectdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectMembersUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectShortcutcancel ledEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectShortcutSetEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicCreatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Topicdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicRenamedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectOperationLogDomainService;
use Delightful\BeDelightful\Infrastructure\Utils\IpUtil;
use Hyperf\Event\Annotation\list ener;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * ItemLogEventlist ener. */ #[Asynclist ener] #[list ener]

class ProjectOperatorLogSubscriber implements list enerInterface 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    private readonly ProjectOperationLogDomainService $projectOperationLogDomainService, 
    private readonly ProjectMemberDomainService $projectMemberDomainService, 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly RequestInterface $request, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 /** * list enEventlist . */ 
    public function listen(): array 
{
 return [ // ItemEvent ProjectCreatedEvent::class, ProjectUpdatedEvent::class, Projectdelete dEvent::class, // topic Event TopicCreatedEvent::class, TopicUpdatedEvent::class, Topicdelete dEvent::class, TopicRenamedEvent::class, // FileEvent FileUploadedEvent::class, Filedelete dEvent::class, FileRenamedEvent::class, FileMovedEvent::class, FileContentSavedEvent::class, FileReplacedEvent::class, Directorydelete dEvent::class, FileBatchMoveEvent::class, FilesBatchdelete dEvent::class, // ItemMemberEvent ProjectMembersUpdatedEvent::class, // Itemshortcut Event ProjectShortcutSetEvent::class, ProjectShortcutcancel ledEvent::class, ]; 
}
 /** * process Event. */ 
    public function process(object $event): void 
{
 // Debug: record ReceiveEvent $this->logger->info('ProjectOperatorLogSubscriber received event', [ 'event_class' => get_class($event), ]); // Using defer Delayedprocess MainBusiness process try 
{
 $ip = IpUtil::getClientIpAddress($this->request); $operationLogEntity = $this->convertEventToEntity($event, $ip); if ($operationLogEntity !== null) 
{
 $this->projectOperationLogDomainService->saveOperationLog($operationLogEntity); $this->logger->info('Project operation log has been saved', [ 'event_class' => get_class($event), 'project_id' => $operationLogEntity->getProjectId(), 'action' => $operationLogEntity->getOperationAction(), ]); 
}
 // UpdateItemMemberactive Time $this->updateuser LastActiveTime($event); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Save project operation log failed', [ 'event_class' => get_class($event), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 
    private function updateuser LastActiveTime(object $event): void 
{
 $projectId = null; $userId = null; $organizationCode = null; // UpdateItemMemberactive Time switch (true) 
{
 case $event instanceof ProjectUpdatedEvent: $projectId = $event->getProjectEntity()->getId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof FileUploadedEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Id(); $organizationCode = $event->getOrganizationCode(); break; case $event instanceof Filedelete dEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Id(); $organizationCode = $event->getOrganizationCode(); break; case $event instanceof FileRenamedEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof FileMovedEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof FileContentSavedEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Id(); $organizationCode = $event->getOrganizationCode(); break; case $event instanceof FileReplacedEvent: $projectId = $event->getFileEntity()->getProjectId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof Directorydelete dEvent: $projectId = $event->getDirectoryEntity()->getProjectId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof FileBatchMoveEvent: $projectId = $event->getProjectId(); $userId = $event->getuser Id(); $organizationCode = $event->getOrganizationCode(); break; case $event instanceof FilesBatchdelete dEvent: $projectId = $event->getProjectId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof ProjectMembersUpdatedEvent: $projectId = $event->getProjectEntity()->getId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof ProjectShortcutSetEvent: $projectId = $event->getProjectEntity()->getId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; case $event instanceof ProjectShortcutcancel ledEvent: $projectId = $event->getProjectEntity()->getId(); $userId = $event->getuser Authorization()->getId(); $organizationCode = $event->getuser Authorization()->getOrganizationCode(); break; 
}
 if ($projectId !== null) 
{
 $this->projectMemberDomainService->updateuser LastActiveTime($userId, $projectId, $organizationCode); $this->projectDomainService->updateUpdatedAtToNow($projectId); 
}
 
}
 /** * EventConvert toLog. */ 
    private function convertEventToEntity(object $event, string $ip): ?ProjectOperationLogEntity 
{
 $entity = new ProjectOperationLogEntity(); switch (true) 
{
 case $event instanceof ProjectCreatedEvent: $project = $event->getProjectEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::CREATE_PROJECT); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); $entity->setOperationDetails([ 'project_name' => $project->getProjectName(), 'project_description' => $project->getProjectDescription(), 'project_mode' => $project->getProjectMode(), ]); break; case $event instanceof ProjectUpdatedEvent: $project = $event->getProjectEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::UPDATE_PROJECT); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); $entity->setOperationDetails([ 'project_name' => $project->getProjectName(), 'project_description' => $project->getProjectDescription(), ]); break; case $event instanceof Projectdelete dEvent: $project = $event->getProjectEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::DELETE_PROJECT); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); break; case $event instanceof TopicCreatedEvent: $topic = $event->getTopicEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($topic->getProjectId()); $entity->setOperationAction(OperationAction::CREATE_TOPIC); $entity->setResourceType(ResourceType::TOPIC); $entity->setResourceId((string) $topic->getId()); $entity->setOperationDetails([ 'topic_name' => $topic->getTopicName(), ]); break; case $event instanceof TopicUpdatedEvent: $topic = $event->getTopicEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($topic->getProjectId()); $entity->setOperationAction(OperationAction::UPDATE_TOPIC); $entity->setResourceType(ResourceType::TOPIC); $entity->setResourceId((string) $topic->getId()); $entity->setOperationDetails([ 'topic_name' => $topic->getTopicName(), ]); break; case $event instanceof Topicdelete dEvent: $topic = $event->getTopicEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($topic->getProjectId()); $entity->setOperationAction(OperationAction::DELETE_TOPIC); $entity->setResourceType(ResourceType::TOPIC); $entity->setResourceId((string) $topic->getId()); break; case $event instanceof TopicRenamedEvent: $topic = $event->getTopicEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($topic->getProjectId()); $entity->setOperationAction(OperationAction::RENAME_TOPIC); $entity->setResourceType(ResourceType::TOPIC); $entity->setResourceId((string) $topic->getId()); $entity->setOperationDetails([ 'topic_name' => $topic->getTopicName(), ]); break; case $event instanceof FileUploadedEvent: $file = $event->getFileEntity(); // Getuser Authorizeinfo $entity->setuser Id($event->getuser Id()); $entity->setOrganizationCode($event->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::UPLOAD_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); $entity->setOperationDetails([ 'file_name' => $file->getFileName(), 'parent_id' => $file->getParentId(), 'is_directory' => $file->getIsDirectory(), ]); break; case $event instanceof Filedelete dEvent: $file = $event->getFileEntity(); // Getuser Authorizeinfo $entity->setuser Id($event->getuser Id()); $entity->setOrganizationCode($event->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::DELETE_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); break; case $event instanceof FileRenamedEvent: $file = $event->getFileEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::RENAME_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); $entity->setOperationDetails([ 'file_name' => $file->getFileName(), 'parent_id' => $file->getParentId(), 'is_directory' => $file->getIsDirectory(), ]); break; case $event instanceof FileMovedEvent: $file = $event->getFileEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::MOVE_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); $entity->setOperationDetails([ 'file_name' => $file->getFileName(), 'parent_id' => $file->getParentId(), 'is_directory' => $file->getIsDirectory(), ]); break; case $event instanceof FileContentSavedEvent: $file = $event->getFileEntity(); // Getuser Authorizeinfo $entity->setuser Id($event->getuser Id()); $entity->setOrganizationCode($event->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::SAVE_FILE_CONTENT); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); $entity->setOperationDetails([]); break; case $event instanceof FileReplacedEvent: $file = $event->getFileEntity(); $versionEntity = $event->getVersionEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($file->getProjectId()); $entity->setOperationAction(OperationAction::REPLACE_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId((string) $file->getFileId()); // info $operationDetails = [ 'file_name' => $file->getFileName(), 'file_extension' => $file->getFileExtension(), 'file_size' => $file->getFileSize(), 'is_cross_type_replace' => $event->isCrossTypeReplace(), ]; // IfCreateed Versionrecord VersionID if ($versionEntity !== null) 
{
 $operationDetails['version_id'] = $versionEntity->getId(); 
}
 $entity->setOperationDetails($operationDetails); break; case $event instanceof Directorydelete dEvent: $directory = $event->getDirectoryEntity(); // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($directory->getProjectId()); $entity->setOperationAction(OperationAction::DELETE_DIRECTORY); $entity->setResourceType(ResourceType::DIRECTORY); $entity->setResourceId((string) $directory->getFileId()); break; case $event instanceof FileBatchMoveEvent: $entity->setuser Id($event->getuser Id()); $entity->setOrganizationCode($event->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($event->getProjectId()); $entity->setOperationAction(OperationAction::BATCH_MOVE_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId('batch'); $entity->setOperationDetails([ 'parent_id' => $event->getTargetParentId(), 'file_ids' => $event->getFileIds(), ]); break; case $event instanceof FilesBatchdelete dEvent: // Getuser Authorizeinfo $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($event->getProjectId()); $entity->setOperationAction(OperationAction::BATCH_DELETE_FILE); $entity->setResourceType(ResourceType::FILE); $entity->setResourceId('batch'); $entity->setOperationDetails([ 'file_ids' => $event->getFileIds(), ]); break; case $event instanceof ProjectMembersUpdatedEvent: $project = $event->getProjectEntity(); $members = $event->getcurrent Members(); $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::UPDATE_PROJECT_MEMBERS); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); $entity->setOperationDetails(['members' => $members]); break; case $event instanceof ProjectShortcutSetEvent: $project = $event->getProjectEntity(); $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::SET_PROJECT_SHORTCUT); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); $entity->setOperationDetails([ 'workspace_id' => $event->getWorkspaceId(), 'project_name' => $project->getProjectName(), ]); break; case $event instanceof ProjectShortcutcancel ledEvent: $project = $event->getProjectEntity(); $userAuthorization = $event->getuser Authorization(); $entity->setuser Id($userAuthorization->getId()); $entity->setOrganizationCode($userAuthorization->getOrganizationCode()); $entity->setOperationStatus('success'); $entity->setIpAddress($ip); $entity->setProjectId($project->getId()); $entity->setOperationAction(OperationAction::CANCEL_PROJECT_SHORTCUT); $entity->setResourceType(ResourceType::PROJECT); $entity->setResourceId((string) $project->getId()); $entity->setOperationDetails([ 'project_name' => $project->getProjectName(), ]); break; default: $this->logger->warning('UnprocessedEventType', ['event_class' => get_class($event)]); return null; 
}
 return $entity; 
}
 
}
 
