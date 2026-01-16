<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Application\Chat\Service\MagicChatMessageAppService;
use App\Application\File\Service\FileAppService;
use App\Application\File\Service\FileCleanupAppService;
use App\Domain\Chat\Service\MagicConversationDomainService;
use App\Domain\Chat\Service\MagicTopicDomainService as MagicChatTopicDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\MagicDepartmentDomainService;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\LongTermMemory\Service\LongTermMemoryDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\SuperAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\CreationSource;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\delete DataType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Volcengine\SandboxService;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetWorkspaceTopicsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveWorkspaceRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\Workspacelist RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\MessageItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\SaveWorkspaceResultDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\Topiclist ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\WorkspaceItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\Workspacelist ResponseDTO;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class WorkspaceAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected MagicChatMessageAppService $magicChatMessageAppService, 
    protected MagicDepartmentDomainService $magicDepartmentDomainService, 
    protected WorkspaceDomainService $workspaceDomainService, 
    protected MagicConversationDomainService $magicConversationDomainService, 
    protected Magicuser DomainService $userDomainService, 
    protected MagicChatTopicDomainService $magicTopicDomainService, 
    protected FileAppService $fileAppService, 
    protected TaskDomainService $taskDomainService, 
    protected AccountAppService $accountAppService, 
    protected SandboxService $sandboxService, 
    protected LockerInterface $locker, 
    protected ChatAppService $chatAppService, 
    protected ProjectDomainService $projectDomainService, 
    protected TopicDomainService $topicDomainService, 
    protected Producer $producer, 
    protected LoggerFactory $loggerFactory, 
    protected FileCleanupAppService $fileCleanupAppService, 
    protected FileDomainService $fileDomainService, 
    protected LongTermMemoryDomainService $longTermMemoryDomainService ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); 
}
 /** * Getworkspace list . */ 
    public function getWorkspacelist (RequestContext $requestContext, Workspacelist RequestDTO $requestDTO): Workspacelist ResponseDTO 
{
 // Buildquery Condition $conditions = $requestDTO->buildConditions(); // IfDon't havespecified user IDand Haveuser Authorizeinfo Usingcurrent user ID if (empty($conditions['user_id'])) 
{
 $conditions['user_id'] = $requestContext->getuser Authorization()->getId(); 
}
 // CreateDataObject $dataIsolation = $this->createDataIsolation($requestContext->getuser Authorization()); // ThroughServiceGetworkspace list $result = $this->workspaceDomainService->getWorkspacesByConditions( $conditions, $requestDTO->page, $requestDTO->pageSize, 'id', 'desc', $dataIsolation ); // Set Default value $result['auto_create'] = false; if (empty($result['list'])) 
{
 $workspaceEntity = $this->workspaceDomainService->createWorkspace( $dataIsolation, '', '' ); $result['list'] = [$workspaceEntity->toArray()]; $result['total'] = 1; $result['auto_create'] = true; 
}
 // Allworkspace ID $workspaceIds = []; foreach ($result['list'] as $workspace) 
{
 if (is_array($workspace)) 
{
 $workspaceIds[] = $workspace['id']; 
}
 else 
{
 $workspaceIds[] = $workspace->getId(); 
}
 
}
 $workspaceIds = array_unique($workspaceIds); // BatchGetworkspace Status $currentuser Id = $dataIsolation->getcurrent user Id(); $workspaceStatusMap = $this->topicDomainService->calculateWorkspaceStatusBatch($workspaceIds, $currentuser Id); // Convert toResponseDTOStatusMap return Workspacelist ResponseDTO::fromResult($result, $workspaceStatusMap); 
}
 /** * Getworkspace Details. */ 
    public function getWorkspaceDetail(RequestContext $requestContext, int $workspaceId): WorkspaceItemDTO 
{
 // CreateDataObject $dataIsolation = $this->createDataIsolation($requestContext->getuser Authorization()); // Getworkspace Details $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($workspaceId); if ($workspaceEntity === null) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found'); 
}
 // Validate workspace whether belongs to current user if ($workspaceEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_ACCESS_DENIED, 'workspace.access_denied'); 
}
 // Calculate workspace Status $workspaceStatusMap = $this->topicDomainService->calculateWorkspaceStatusBatch([$workspaceId]); $workspaceStatus = $workspaceStatusMap[$workspaceId] ?? null; // Return workspace DetailsDTO return WorkspaceItemDTO::fromEntity($workspaceEntity, $workspaceStatus); 
}
 
    public function createWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); $workspaceEntity = $this->workspaceDomainService->createWorkspace( $dataIsolation, '', $requestDTO->getWorkspaceName() ); return SaveWorkspaceResultDTO::fromId((int) $workspaceEntity->getId()); 
}
 
    public function updateWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); if (empty($requestDTO->getWorkspaceId())) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND); 
}
 $this->workspaceDomainService->updateWorkspace($dataIsolation, (int) $requestDTO->getWorkspaceId(), $requestDTO->getWorkspaceName()); return SaveWorkspaceResultDTO::fromId((int) $requestDTO->getWorkspaceId()); 
}
 /** * Save workspace (create or update). * @return SaveWorkspaceResultDTO Operation result, including workspace ID * @throws BusinessException Throws an exception if saving fails * @throws Throwable */ 
    public function saveWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO 
{
 Db::beginTransaction(); try 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // Prepare workspace entity if ($requestDTO->getWorkspaceId()) 
{
 // Update, currently only updates workspace name $this->workspaceDomainService->updateWorkspace($dataIsolation, (int) $requestDTO->getWorkspaceId(), $requestDTO->getWorkspaceName()); Db::commit(); return SaveWorkspaceResultDTO::fromId((int) $requestDTO->getWorkspaceId()); 
}
 // SubmitTransaction Db::commit(); // Create, use provided workspace name if available;
otherwise use default name $result = $this->inituser Workspace($dataIsolation, $requestDTO->getWorkspaceName());
return SaveWorkspaceResultDTO::fromId($result['workspace']->getId()); 
}
 catch (EventException $e) 
{
 // RollbackTransaction Db::rollReturn (); $this->logger->error(sprintf( Error creating new workspace event: %s\n%s , $e->getMessage(), $e->getTraceAsString())); ExceptionBuilder::throw(SuperAgentErrorCode::CREATE_TOPIC_FAILED, $e->getMessage()); 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error(sprintf( Error creating new workspace: %s\n%s , $e->getMessage(), $e->getTraceAsString())); ExceptionBuilder::throw(SuperAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed'); 
}
 
}
 /** * Getworkspace under topic list . */ 
    public function getWorkspaceTopics(RequestContext $requestContext, GetWorkspaceTopicsRequestDTO $dto): Topiclist ResponseDTO 
{
 // CreateDataObject $dataIsolation = $this->createDataIsolation($requestContext->getuser Authorization()); // ThroughServiceGetworkspace topic list $result = $this->workspaceDomainService->getWorkspaceTopics( [$dto->getWorkspaceId()], $dataIsolation, true, $dto->getPageSize(), $dto->getPage(), $dto->getOrderBy(), $dto->getOrderDirection() ); // Convert toResponse DTO return Topiclist ResponseDTO::fromResult($result); 
}
 /** * GetTasklist . */ 
    public function getTaskAttachments(Magicuser Authorization $userAuthorization, int $taskId, int $page = 1, int $pageSize = 10): array 
{
 // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); // GetTasklist $result = $this->workspaceDomainService->getTaskAttachments($taskId, $dataIsolation, $page, $pageSize); // process File URL $list = []; $organizationCode = $userAuthorization->getOrganizationCode(); $fileKeys = []; // list UsingTaskFileItemDTOprocess foreach ($result['list'] as $entity) 
{
 // CreateDTO $dto = new TaskFileItemDTO(); $dto->fileId = (string) $entity->getFileId(); $dto->taskId = (string) $entity->getTaskId(); $dto->fileType = $entity->getFileType(); $dto->fileName = $entity->getFileName(); $dto->fileExtension = $entity->getFileExtension(); $dto->fileKey = $entity->getFileKey(); $dto->fileSize = $entity->getFileSize(); $dto->topicId = (string) $entity->getTopicId(); // Add file_url Field $fileKey = $entity->getFileKey(); if (! empty($fileKey)) 
{
 $fileLink = $this->fileAppService->getLink($organizationCode, $fileKey, StorageBucketType::SandBox); if ($fileLink) 
{
 $dto->fileUrl = $fileLink->getUrl(); 
}
 else 
{
 $dto->fileUrl = ''; 
}
 
}
 else 
{
 $dto->fileUrl = ''; 
}
 // Determinefilekeywhether DuplicateIfDuplicateSkip if (in_array($fileKey, $fileKeys)) 
{
 continue; 
}
 $fileKeys[] = $fileKey; $list[] = $dto->toArray(); 
}
 return [ 'list' => $list, 'total' => $result['total'], ]; 
}
 /** * delete workspace . * * @param RequestContext $requestContext RequestContext * @param int $workspaceId workspace ID * @return bool whether delete Success * @throws BusinessException Ifuser No permissionor workspace does not existThrowException */ 
    public function deleteWorkspace(RequestContext $requestContext, int $workspaceId): bool 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); // call Serviceexecute delete Db::beginTransaction(); try 
{
 // Getworkspace under AllProject IDfor delete long-term memory $projectIds = $this->projectDomainService->getProjectIdsByWorkspaceId($dataIsolation, $workspaceId); // Batchdelete Itemrelated long-term memory if (! empty($projectIds)) 
{
 $this->longTermMemoryDomainService->deleteMemoriesByProjectIds( $dataIsolation->getcurrent OrganizationCode(), AgentConstant::SUPER_MAGIC_CODE, $dataIsolation->getcurrent user Id(), $projectIds ); 
}
 // delete workspace $this->workspaceDomainService->deleteWorkspace($dataIsolation, $workspaceId); // delete workspace under Item $this->projectDomainService->deleteProjectsByWorkspaceId($dataIsolation, $workspaceId); // delete topic $this->topicDomainService->deleteTopicsByWorkspaceId($dataIsolation, $workspaceId); // delivery MessageStopAllRunningTask $event = new StopRunningTaskEvent( delete DataType::WORKSPACE, $workspaceId, $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), 'workspace delete ' ); $publisher = new StopRunningTaskPublisher($event); $this->producer->produce($publisher); $this->logger->info(sprintf( 'delivery StopTaskMessageworkspace ID: %d, EventID: %s', $workspaceId, $event->getEventId() )); Db::commit(); 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error('delete workspace Failed' . $e->getMessage()); throw $e; 
}
 return true; 
}
 /** * GetTaskDetails. * * @param RequestContext $requestContext RequestContext * @param int $taskId TaskID * @return array TaskDetails * @throws BusinessException Ifuser No permissionor Taskdoes not existThrowException */ 
    public function getTaskDetail(RequestContext $requestContext, int $taskId): array 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); // GetTaskDetails $taskEntity = $this->workspaceDomainService->getTaskById($taskId); if (! $taskEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::SystemError, 'task.not_found'); 
}
 return $taskEntity->toArray(); 
}
 /** * Gettopic Messagelist . * * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageSize * @param string $sortDirection SortSupportascdesc * @return array Messagelist Total */ 
    public function getMessagesByTopicId(int $topicId, int $page = 1, int $pageSize = 20, string $sortDirection = 'asc'): array 
{
 // GetMessagelist $result = $this->taskDomainService->getMessagesByTopicId($topicId, $page, $pageSize, true, $sortDirection); // Convert toResponseFormat $messages = []; foreach ($result['list'] as $message) 
{
 $messages[] = new MessageItemDTO($message->toArray()); 
}
 $data = [ 'list' => $messages, 'total' => $result['total'], ]; // Get topic info $topicEntity = $this->topicDomainService->getTopicWithdelete d($topicId); if ($topicEntity != null) 
{
 $data['project_id'] = (string) $topicEntity->getProjectId(); $projectEntity = $this->getAccessibleProject($topicEntity->getProjectId(), $topicEntity->getuser Id(), $topicEntity->getuser OrganizationCode()); $data['project_name'] = $projectEntity->getProjectName(); 
}
 return $data; 
}
 /** * Set workspace Status. * * @param RequestContext $requestContext RequestContext * @param array $workspaceIds workspace IDArray * @param int $isArchived Status0:not archived , 1:Archived * @return bool whether Success */ 
    public function setWorkspaceArchived(RequestContext $requestContext, array $workspaceIds, int $isArchived): bool 
{
 // CreateDataObject $dataIsolation = $this->createDataIsolation($requestContext->getuser Authorization()); $currentuser Id = $dataIsolation->getcurrent user Id(); // ParameterValidate if (empty($workspaceIds)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'workspace.ids_required'); 
}
 // Validate StatusValuewhether valid if (! in_array($isArchived, [ WorkspaceArchiveStatus::NotArchived->value, WorkspaceArchiveStatus::Archived->value, ])) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.invalid_archive_status'); 
}
 // BatchUpdateworkspace Status $success = true; foreach ($workspaceIds as $workspaceId) 
{
 // Getworkspace DetailsValidate All $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail((int) $workspaceId); // Ifworkspace does not existSkip if (! $workspaceEntity) 
{
 $success = false; continue; 
}
 // Validate workspace whether belongs to current user if ($workspaceEntity->getuser Id() !== $currentuser Id) 
{
 ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'workspace.not_owner'); 
}
 // call ServiceSet Status $result = $this->workspaceDomainService->archiveWorkspace( $requestContext, (int) $workspaceId, $isArchived === WorkspaceArchiveStatus::Archived->value ); if (! $result) 
{
 $success = false; 
}
 
}
 return $success; 
}
 /** * GetFileURLlist . * * @param Magicuser Authorization $userAuthorization user Authorizeinfo * @param array $fileIds FileIDlist * @param string $downloadMode DownloadSchemadownload:Download, preview:Preview * @param array $options Options * @return array FileURLlist */ 
    public function getFileUrls(Magicuser Authorization $userAuthorization, array $fileIds, string $downloadMode, array $options = []): array 
{
 // CreateDataObject $organizationCode = $userAuthorization->getOrganizationCode(); $result = []; foreach ($fileIds as $fileId) 
{
 // GetFile $fileEntity = $this->taskDomainService->getTaskFile((int) $fileId); if (empty($fileEntity)) 
{
 // IfFiledoes not existSkip continue; 
}
 // Validate Filewhether belongs to current user $projectEntity = $this->getAccessibleProject($fileEntity->getProjectId(), $userAuthorization->getId(), $organizationCode); $downloadNames = []; if ($downloadMode === 'download') 
{
 $downloadNames[$fileEntity->getFileKey()] = $fileEntity->getFileName(); 
}
 $fileLink = $this->fileAppService->getLink($organizationCode, $fileEntity->getFileKey(), StorageBucketType::SandBox, $downloadNames, $options); if (empty($fileLink)) 
{
 // IfGetURLFailedSkip continue; 
}
 // AddSuccessResult $result[] = [ 'file_id' => $fileId, 'url' => $fileLink->getUrl(), ]; 
}
 return $result; 
}
 
    public function getTopicDetail(int $topicId): string 
{
 $topicEntity = $this->workspaceDomainService->getTopicById($topicId); if (empty($topicEntity)) 
{
 return ''; 
}
 return $topicEntity->getTopicName(); 
}
 /** * Getworkspace info Throughtopic IDCollection. * * @param array $topicIds topic IDCollectionStringArray * @return array topic IDas Keyworkspace info as ValueAssociationArray */ 
    public function getWorkspaceinfo ByTopicIds(array $topicIds): array 
{
 // ConvertStringIDas Integer $intTopicIds = array_map('intval', $topicIds); // call ServiceGetworkspace info return $this->workspaceDomainService->getWorkspaceinfo ByTopicIds($intTopicIds); 
}
 /** * RegisterConvertPDFFileScheduledClean. */ 
    public function registerConvertedPdfsForCleanup(Magicuser Authorization $userAuthorization, array $convertedFiles): void 
{
 if (empty($convertedFiles)) 
{
 return; 
}
 $filesForCleanup = []; foreach ($convertedFiles as $file) 
{
 if (empty($file['oss_key']) || empty($file['filename'])) 
{
 continue; 
}
 $filesForCleanup[] = [ 'organization_code' => $userAuthorization->getOrganizationCode(), 'file_key' => $file['oss_key'], 'file_name' => $file['filename'], 'file_size' => $file['size'] ?? 0, // IfResponsein Don't havesizeDefault to0 'source_type' => 'pdf_conversion', 'source_id' => $file['batch_id'] ?? null, 'expire_after_seconds' => 7200, // 2 'bucket_type' => 'private', ]; 
}
 if (! empty($filesForCleanup)) 
{
 $this->fileCleanupAppService->registerFilesForCleanup($filesForCleanup); $this->logger->info('[PDF Converter] Registered converted PDF files for cleanup', [ 'user_id' => $userAuthorization->getId(), 'files_count' => count($filesForCleanup), ]); 
}
 
}
 /** * Initializeuser workspace . * * @param DataIsolation $dataIsolation DataObject * @param string $workspaceName workspace NameDefault to workspace * @return array CreateResultincluding workspacetopicObjectauto_createFlag * @throws BusinessException IfCreation failedThrowException * @throws Throwable */ 
    private function inituser Workspace( DataIsolation $dataIsolation, string $workspaceName = '' ): array 
{
 $this->logger->info('StartInitializeuser workspace '); Db::beginTransaction(); try 
{
 // Step 1: Initialize Magic Chat Conversation [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initMagicChatConversation($dataIsolation); $this->logger->info(sprintf('InitializeSuper Maggie, chatConversationId=%s, chatConversationTopicId=%s', $chatConversationId, $chatConversationTopicId)); // Step 2: Create workspace $this->logger->info('StartCreateDefault workspace'); $workspaceEntity = $this->workspaceDomainService->createWorkspace( $dataIsolation, $chatConversationId, $workspaceName ); $this->logger->info(sprintf('CreateDefault workspaceSuccess, workspaceId=%s', $workspaceEntity->getId())); if (! $workspaceEntity->getId()) 
{
 ExceptionBuilder::throw(GenericErrorCode::SystemError, 'workspace.create_workspace_failed'); 
}
 // CreateDefault project $this->logger->info('StartCreateDefault project'); $projectEntity = $this->projectDomainService->createProject( $workspaceEntity->getId(), '', $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), '', '', null, CreationSource::USER_CREATED->value ); $this->logger->info(sprintf('CreateDefault projectSuccess, projectId=%s', $projectEntity->getId())); // Getworkspace Directory $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getcurrent user Id(), $projectEntity->getId()); // Step 4: Create default topic $this->logger->info('StartCreateDefault topic'); $topicEntity = $this->topicDomainService->createTopic( $dataIsolation, $workspaceEntity->getId(), $projectEntity->getId(), $chatConversationId, $chatConversationTopicId, '', $workDir ); $this->logger->info(sprintf('CreateDefault topicSuccess, topicId=%s', $topicEntity->getId())); // Step 5: Update workspace current topic if ($topicEntity->getId()) 
{
 // Set workspace info $workspaceEntity->setcurrent TopicId($topicEntity->getId()); $workspaceEntity->setcurrent ProjectId($projectEntity->getId()); $this->workspaceDomainService->saveWorkspaceEntity($workspaceEntity); $this->logger->info(sprintf('workspace %sSet current topic %s', $workspaceEntity->getId(), $topicEntity->getId())); // Set Iteminfo $projectEntity->setcurrent TopicId($topicEntity->getId()); $projectEntity->setWorkspaceId($workspaceEntity->getId()); $projectEntity->setWorkDir($workDir); $this->projectDomainService->saveProjectEntity($projectEntity); $this->logger->info(sprintf('Item%sSet current topic %s', $projectEntity->getId(), $topicEntity->getId())); 
}
 Db::commit(); // Return creation result return [ 'workspace' => $workspaceEntity, 'topic' => $topicEntity, 'project' => $projectEntity, 'auto_create' => true, ]; 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); throw $e; 
}
 
}
 
}
 
