<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Application\Chat\Service\MagicChatMessageAppService;
use App\Application\File\Service\FileAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\SuperAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\TopicDuplicateConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\delete DataType;
use Delightful\BeDelightful\Domain\SuperAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicCreatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Topicdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicRenamedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileTreeUtil;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\delete TopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\DuplicateTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetTopicAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\delete TopicResultDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\MessageItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\SaveTopicResultDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TopicItemDTO;
use Exception;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function Hyperf\Coroutine\go;

class TopicAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected TaskDomainService $taskDomainService, 
    protected WorkspaceDomainService $workspaceDomainService, 
    protected ProjectDomainService $projectDomainService, 
    protected TopicDomainService $topicDomainService, 
    protected ResourceShareDomainService $resourceShareDomainService, 
    protected MagicChatMessageAppService $magicChatMessageAppService, 
    protected FileAppService $fileAppService, 
    protected ChatAppService $chatAppService, 
    protected Producer $producer, 
    protected EventDispatcherInterface $eventDispatcher, 
    protected TopicDuplicateStatusManager $topicDuplicateStatusManager, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); 
}
 
    public function getTopic(RequestContext $requestContext, int $id): TopicItemDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); // Gettopic Content $topicEntity = $this->topicDomainService->getTopicById($id); if (! $topicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // Determinetopic whether yes if ($topicEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied'); 
}
 return TopicItemDTO::fromEntity($topicEntity); 
}
 
    public function getTopicById(int $id): TopicItemDTO 
{
 // Gettopic Content $topicEntity = $this->topicDomainService->getTopicById($id); if (! $topicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 return TopicItemDTO::fromEntity($topicEntity); 
}
 
    public function createTopic(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): TopicItemDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); $projectEntity = $this->getAccessibleProjectWithEditor((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // CreateNewtopic UsingTransactionEnsure Db::beginTransaction(); try 
{
 // 1. Initialize chat Sessiontopic [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initMagicChatConversation($dataIsolation); // 2. Createtopic $topicEntity = $this->topicDomainService->createTopic( $dataIsolation, $projectEntity->getWorkspaceId(), (int) $requestDTO->getProjectId(), $chatConversationId, $chatConversationTopicId, // Session topic ID $requestDTO->getTopicName(), $projectEntity->getWorkDir(), $requestDTO->getTopicMode() ); // 3. Ifed project_modeUpdateItemSchema if (! empty($requestDTO->getProjectMode())) 
{
 $projectEntity->setProjectMode($requestDTO->getProjectMode()); $projectEntity->setUpdatedAt(date('Y-m-d H:i:s')); $this->projectDomainService->saveProjectEntity($projectEntity); 
}
 // SubmitTransaction Db::commit(); // Publishedtopic CreateEvent $topicCreatedEvent = new TopicCreatedEvent($topicEntity, $userAuthorization); $this->eventDispatcher->dispatch($topicCreatedEvent); // Return Result return TopicItemDTO::fromEntity($topicEntity); 
}
 catch (Throwable $e) 
{
 // RollbackTransaction Db::rollReturn (); $this->logger->error(sprintf( Error creating new topic: %s\n%s , $e->getMessage(), $e->getTraceAsString())); ExceptionBuilder::throw(SuperAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed'); 
}
 
}
 
    public function createTopicNotValidateAccessibleProject(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): ?TopicItemDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); $projectEntity = $this->projectDomainService->getProjectNotuser Id((int) $requestDTO->getProjectId()); // CreateNewtopic UsingTransactionEnsure Db::beginTransaction(); try 
{
 // 1. Initialize chat Sessiontopic [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initMagicChatConversation($dataIsolation); // 2. Createtopic $topicEntity = $this->topicDomainService->createTopic( $dataIsolation, (int) $requestDTO->getWorkspaceId(), (int) $requestDTO->getProjectId(), $chatConversationId, $chatConversationTopicId, // Session topic ID $requestDTO->getTopicName(), $projectEntity->getWorkDir(), $requestDTO->getTopicMode(), ); // 3. Ifed project_modeUpdateItemSchema if (! empty($requestDTO->getProjectMode())) 
{
 $projectEntity->setProjectMode($requestDTO->getProjectMode()); $projectEntity->setUpdatedAt(date('Y-m-d H:i:s')); $this->projectDomainService->saveProjectEntity($projectEntity); 
}
 // SubmitTransaction Db::commit(); // Return Result return TopicItemDTO::fromEntity($topicEntity); 
}
 catch (Throwable $e) 
{
 // RollbackTransaction Db::rollReturn (); $this->logger->error(sprintf( Error creating new topic: %s\n%s , $e->getMessage(), $e->getTraceAsString())); ExceptionBuilder::throw(SuperAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed'); 
}
 
}
 
    public function updateTopic(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): SaveTopicResultDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); $this->topicDomainService->updateTopic($dataIsolation, (int) $requestDTO->getId(), $requestDTO->getTopicName()); // GetUpdatetopic for EventPublished $topicEntity = $this->topicDomainService->getTopicById((int) $requestDTO->getId()); // Publishedtopic UpdatedEvent if ($topicEntity) 
{
 $topicUpdatedEvent = new TopicUpdatedEvent($topicEntity, $userAuthorization); $this->eventDispatcher->dispatch($topicUpdatedEvent); 
}
 return SaveTopicResultDTO::fromId((int) $requestDTO->getId()); 
}
 
    public function renameTopic(Magicuser Authorization $authorization, int $topicId, string $userQuestion, string $language = 'zh_CN'): array 
{
 // Gettopic Content $topicEntity = $this->workspaceDomainService->getTopicById($topicId); if (! $topicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // call Serviceexecute Renamemagic-serviceRowBind try 
{
 $text = $this->magicChatMessageAppService->summarizeText($authorization, $userQuestion, $language); // Updatetopic Name $dataIsolation = $this->createDataIsolation($authorization); $this->topicDomainService->updateTopicName($dataIsolation, $topicId, $text); // GetUpdatetopic PublishedRenameEvent $updatedTopicEntity = $this->topicDomainService->getTopicById($topicId); if ($updatedTopicEntity) 
{
 $topicRenamedEvent = new TopicRenamedEvent($updatedTopicEntity, $authorization); $this->eventDispatcher->dispatch($topicRenamedEvent); 
}
 
}
 catch (Exception $e) 
{
 $this->logger->error('rename topic error: ' . $e->getMessage()); $text = $topicEntity->getTopicName(); 
}
 return ['topic_name' => $text]; 
}
 /** * delete topic . * * @param RequestContext $requestContext RequestContext * @param delete TopicRequestDTO $requestDTO RequestDTO * @return delete TopicResultDTO delete Result * @throws BusinessException|Exception Ifuser No permissiontopic does not existor TaskAtRow */ 
    public function deleteTopic(RequestContext $requestContext, delete TopicRequestDTO $requestDTO): delete TopicResultDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); // Gettopic ID $topicId = $requestDTO->getId(); // Gettopic for EventPublished $topicEntity = $this->topicDomainService->getTopicById((int) $topicId); // call Serviceexecute delete $result = $this->topicDomainService->deleteTopic($dataIsolation, (int) $topicId); // delivery EventStopService if ($result) 
{
 // Publishedtopic delete dEvent if ($topicEntity) 
{
 $topicdelete dEvent = new Topicdelete dEvent($topicEntity, $userAuthorization); $this->eventDispatcher->dispatch($topicdelete dEvent); 
}
 $event = new StopRunningTaskEvent( delete DataType::TOPIC, (int) $topicId, $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), 'topic delete ' ); $publisher = new StopRunningTaskPublisher($event); $this->producer->produce($publisher); 
}
 // IfDeletion failedThrowException if (! $result) 
{
 ExceptionBuilder::throw(GenericErrorCode::SystemError, 'topic.delete_failed'); 
}
 // Return delete Result return delete TopicResultDTO::fromId((int) $topicId); 
}
 /** * Getmost recently Update timespecified Timetopic list . * * @param string $timeThreshold TimeThresholdIftopic Update timeTimeincluding AtResultin * @param int $limit Return ResultMaximumQuantity * @return array<TopicEntity> topic list */ 
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array 
{
 return $this->topicDomainService->getTopicsExceedingUpdateTime($timeThreshold, $limit); 
}
 
    public function getTopicByChatTopicId(DataIsolation $dataIsolation, string $chatTopicId): ?TopicEntity 
{
 return $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $chatTopicId); 
}
 
    public function getTopicAttachmentsByAccessToken(GetTopicAttachmentsRequestDTO $requestDto): array 
{
 $token = $requestDto->getToken(); // FromGetData if (! AccessTokenUtil::validate($token)) 
{
 ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'task_file.access_denied'); 
}
 // Fromtoken GetContent $shareId = AccessTokenUtil::getShareId($token); $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found'); 
}
 if ($shareEntity->getResourceType() != ResourceType::Topic->value) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported'); 
}
 $organizationCode = AccessTokenUtil::getOrganizationCode($token); $requestDto->setTopicId($shareEntity->getResourceId()); // CreateDataIsolation $dataIsolation = DataIsolation::simpleMake($organizationCode, ''); return $this->getTopicAttachmentlist ($dataIsolation, $requestDto); 
}
 
    public function getTopicAttachmentlist (DataIsolation $dataIsolation, GetTopicAttachmentsRequestDTO $requestDto): array 
{
 // Determinetopic whether Exist $topicEntity = $this->topicDomainService->getTopicById((int) $requestDto->getTopicId()); if (empty($topicEntity)) 
{
 return []; 
}
 $projectEntity = $this->projectDomainService->getProjectNotuser Id($topicEntity->getProjectId()); $sandboxId = $topicEntity->getSandboxId(); $workDir = $topicEntity->getWorkDir(); // ThroughServiceGettopic list $result = $this->taskDomainService->getTaskAttachmentsByTopicId( (int) $requestDto->getTopicId(), $dataIsolation, $requestDto->getPage(), $requestDto->getPageSize(), $requestDto->getFileType() ); // process File URL $list = []; $projectOrganizationCode = $projectEntity->getuser OrganizationCode(); // list UsingTaskFileItemDTOprocess foreach ($result['list'] as $entity) 
{
 // CreateDTO $dto = new TaskFileItemDTO(); $dto->fileId = (string) $entity->getFileId(); $dto->taskId = (string) $entity->getTaskId(); $dto->fileType = $entity->getFileType(); $dto->fileName = $entity->getFileName(); $dto->fileExtension = $entity->getFileExtension(); $dto->fileKey = $entity->getFileKey(); $dto->fileSize = $entity->getFileSize(); $dto->isHidden = $entity->getIsHidden(); $dto->topicId = (string) $entity->getTopicId(); // Calculate relative file path by removing workDir from fileKey $fileKey = $entity->getFileKey(); $workDirPos = strpos($fileKey, $workDir); if ($workDirPos !== false) 
{
 $dto->relativeFilePath = substr($fileKey, $workDirPos + strlen($workDir)); 
}
 else 
{
 $dto->relativeFilePath = $fileKey; // If workDir not found, use original fileKey 
}
 // Add file_url Field $fileKey = $entity->getFileKey();
if (! empty($fileKey)) 
{
 $fileLink = $this->fileAppService->getLink($projectOrganizationCode, $fileKey, StorageBucketType::SandBox); if ($fileLink) 
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
 $list[] = $dto->toArray(); 
}
 // BuildTreeStructure $tree = FileTreeUtil::assembleFilesTree($list); return [ 'list' => $list, 'tree' => $tree, 'total' => $result['total'], ]; 
}
 /** * Gettopic list .(Using). * * @param Magicuser Authorization $userAuthorization user Authorizeinfo * @param GetTopicAttachmentsRequestDTO $requestDto topic RequestDTO * @return array list */ 
    public function getTopicAttachments(Magicuser Authorization $userAuthorization, GetTopicAttachmentsRequestDTO $requestDto): array 
{
 // Getcurrent topic creator $topicEntity = $this->topicDomainService->getTopicById((int) $requestDto->getTopicId()); if ($topicEntity === null) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // CreateDataObject $dataIsolation = $this->createDataIsolation($userAuthorization); return $this->getTopicAttachmentlist ($dataIsolation, $requestDto); 
}
 /** * Getuser topic Messagelist . * * @param Magicuser Authorization $userAuthorization user Authorizeinfo * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageSize * @param string $sortDirection Sort * @return array Messagelist Total */ 
    public function getuser TopicMessage(Magicuser Authorization $userAuthorization, int $topicId, int $page, int $pageSize, string $sortDirection): array 
{
 // GetMessagelist $result = $this->taskDomainService->getMessagesByTopicId($topicId, $page, $pageSize, true, $sortDirection); // Convert toResponseFormat $messages = []; foreach ($result['list'] as $message) 
{
 $messages[] = new MessageItemDTO($message->toArray()); 
}
 return [ 'list' => $messages, 'total' => $result['total'], ]; 
}
 /** * Getuser topic URL. (Using). * * @param string $topicId topic ID * @param Magicuser Authorization $userAuthorization user Authorizeinfo * @param array $fileIds FileIDlist * @return array including URL Array */ 
    public function getTopicAttachmentUrl(Magicuser Authorization $userAuthorization, string $topicId, array $fileIds, string $downloadMode): array 
{
 $result = []; foreach ($fileIds as $fileId) 
{
 // GetFile $fileEntity = $this->taskDomainService->getTaskFile((int) $fileId); if (empty($fileEntity)) 
{
 // IfFiledoes not existSkip continue; 
}
 $downloadNames = []; if ($downloadMode == 'download') 
{
 $downloadNames[$fileEntity->getFileKey()] = $fileEntity->getFileName(); 
}
 $fileLink = $this->fileAppService->getLink($fileEntity->getOrganizationCode(), $fileEntity->getFileKey(), StorageBucketType::SandBox, $downloadNames); if (empty($fileLink)) 
{
 // IfGetLinkFailedSkip continue; 
}
 $result[] = [ 'file_id' => (string) $fileEntity->getFileId(), 'url' => $fileLink->getUrl(), ]; 
}
 return $result; 
}
 /** * Duplicate topic (synchronous) - blocks until completion. * This method performs complete topic duplication synchronously without task management. * * @param RequestContext $requestContext Request context * @param string $sourceTopicId Source topic ID * @param DuplicateTopicRequestDTO $requestDTO Request DTO * @return array complete result with topic info * @throws BusinessException If validation fails or operation fails */ 
    public function duplicateTopic(RequestContext $requestContext, string $sourceTopicId, DuplicateTopicRequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $this->logger->info('Topic duplication sync started', [ 'user_id' => $userAuthorization->getId(), 'source_topic_id' => $sourceTopicId, 'target_message_id' => $requestDTO->getTargetMessageId(), 'new_topic_name' => $requestDTO->getNewTopicName(), ]); // Validate topic and permissions $sourceTopicEntity = $this->topicDomainService->getTopicById((int) $sourceTopicId); if (! $sourceTopicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 if ($sourceTopicEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied'); 
}
 // Create data isolation $dataIsolation = $this->createDataIsolation($userAuthorization); // execute complete duplication in transaction Db::beginTransaction(); try 
{
 // Call domain service - pure business logic $newTopicEntity = $this->topicDomainService->duplicateTopic( $dataIsolation, $sourceTopicEntity, $requestDTO->getNewTopicName(), (int) $requestDTO->getTargetMessageId() ); Db::commit(); $this->logger->info('Topic duplication sync completed', [ 'source_topic_id' => $sourceTopicId, 'new_topic_id' => $newTopicEntity->getId(), ]); // Return complete result return [ 'status' => 'completed', 'message' => 'Topic duplicated successfully', 'topic' => TopicItemDTO::fromEntity($newTopicEntity)->toArray(), ]; 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error('Topic duplication sync failed', [ 'source_topic_id' => $sourceTopicId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); throw $e; 
}
 
}
 /** * Duplicate topic (asynchronous) - returns immediately with task_id. * This method creates topic skeleton synchronously, then copies messages asynchronously. * * @param RequestContext $requestContext Request context * @param string $sourceTopicId Source topic ID * @param DuplicateTopicRequestDTO $requestDTO Request DTO * @return array Task info with task_id * @throws BusinessException If validation fails or operation fails */ 
    public function duplicateChatAsync(RequestContext $requestContext, string $sourceTopicId, DuplicateTopicRequestDTO $requestDTO): array 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); $this->logger->info('Starting topic duplication async (skeleton sync + message copy async)', [ 'user_id' => $userAuthorization->getId(), 'source_topic_id' => $sourceTopicId, 'target_message_id' => $requestDTO->getTargetMessageId(), 'new_topic_name' => $requestDTO->getNewTopicName(), ]); // Validate topic Existpermission $sourceTopicEntity = $this->topicDomainService->getTopicById((int) $sourceTopicId); if (! $sourceTopicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // Determinetopic whether yes if ($sourceTopicEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied'); 
}
 // === SyncPartialCreatetopic === $dataIsolation = $this->createDataIsolation($userAuthorization); // AtTransactionin Createtopic Db::beginTransaction(); try 
{
 // call Domain Createtopic including IM Session $duplicateResult = $this->topicDomainService->duplicateTopicSkeleton( $dataIsolation, $sourceTopicEntity, $requestDTO->getNewTopicName() ); $newTopicEntity = $duplicateResult['topic_entity']; $imConversationResult = $duplicateResult['im_conversation']; // SubmitTransaction Db::commit(); $this->logger->info('Topic skeleton created successfully (sync)', [ 'source_topic_id' => $sourceTopicId, 'new_topic_id' => $newTopicEntity->getId(), ]); 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error('Failed to create topic skeleton (sync)', [ 'source_topic_id' => $sourceTopicId, 'error' => $e->getMessage(), ]); throw $e; 
}
 // topic Convert to DTO $topicItemDTO = TopicItemDTO::fromEntity($newTopicEntity); // Generate TaskKey $taskKey = TopicDuplicateConstant::generateTaskKey($sourceTopicId, $userAuthorization->getId()); // InitializeAsyncTask $taskData = [ 'source_topic_id' => $sourceTopicId, 'target_message_id' => $requestDTO->getTargetMessageId(), 'new_topic_name' => $requestDTO->getNewTopicName(), 'user_id' => $userAuthorization->getId(), 'new_topic_id' => $newTopicEntity->getId(), // SaveNewtopic ID 'im_conversation' => $imConversationResult, // Save IM Sessioninfo ]; $this->topicDuplicateStatusManager->initializeTask($taskKey, $userAuthorization->getId(), $taskData); // Getcurrent RequestID $requestId = CoContext::getRequestId() ?: (string) IdGenerator::getSnowId(); // === AsyncPartialCopyMessage === go(function () use ($sourceTopicEntity, $newTopicEntity, $requestDTO, $imConversationResult, $taskKey, $requestId) 
{
 // CopyRequestContext CoContext::setRequestId($requestId);
try 
{
 // UpdateTaskStatusStartCopyMessage $this->topicDuplicateStatusManager->setTaskProgress($taskKey, 10, 'Starting to copy messages'); // StartDatabaseTransaction Db::beginTransaction(); try 
{
 // execute MessageCopy $this->topicDomainService->copyTopicMessageFromOthers( $sourceTopicEntity, $newTopicEntity, (int) $requestDTO->getTargetMessageId(), $imConversationResult, // Function function (string $status, int $progress, string $message) use ($taskKey) 
{
 $this->topicDuplicateStatusManager->setTaskProgress($taskKey, $progress, $message);

}
 ); // SubmitTransaction Db::commit(); // Taskcomplete $this->topicDuplicateStatusManager->setTaskcomplete d($taskKey, [ 'topic_id' => $newTopicEntity->getId(), 'topic_name' => $newTopicEntity->getTopicName(), 'project_id' => $newTopicEntity->getProjectId(), 'workspace_id' => $newTopicEntity->getWorkspaceId(), ]); $this->logger->info('Topic message copy completed successfully (async)', [ 'task_key' => $taskKey, 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_id' => $newTopicEntity->getId(), ]); 
}
 catch (Throwable $e) 
{
 // RollbackTransaction Db::rollReturn (); throw $e; // NewThrowExceptioncatchprocess 
}
 
}
 catch (Throwable $e) 
{
 // TaskFailed $this->topicDuplicateStatusManager->setTaskFailed($taskKey, $e->getMessage()); $this->logger->error('Async topic message copy failed', [ 'task_key' => $taskKey, 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_id' => $newTopicEntity->getId(), 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
); // Return Taskinfo NewCreatetopic return [ 'task_id' => $taskKey, 'status' => 'copying', 'message' => 'Topic created, copying messages in background', 'topic' => $topicItemDTO->toArray(), // NewReturn topic info ]; 
}
 /** * check topic CopyStatus * * @param RequestContext $requestContext RequestContext * @param string $taskKey TaskKey * @return array CopyStatusinfo * @throws BusinessException IfParameterInvalidor FailedThrowException */ 
    public function checkDuplicateChatStatus(RequestContext $requestContext, string $taskKey): array 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); $this->logger->info('check ing topic duplication status', [ 'user_id' => $userAuthorization->getId(), 'task_key' => $taskKey, ]); try 
{
 // Validate user permission if (! $this->topicDuplicateStatusManager->verifyuser permission ($taskKey, $userAuthorization->getId())) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TASK_ACCESS_DENIED, 'Task access denied'); 
}
 // GetTaskStatus $taskStatus = $this->topicDuplicateStatusManager->getTaskStatus($taskKey); if (! $taskStatus) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TASK_NOT_FOUND, 'Task not found or expired'); 
}
 // BuildReturn Result $result = [ 'task_id' => $taskKey, 'status' => $taskStatus['status'], // running, completed, failed 'message' => $taskStatus['message'] ?? 'Topic duplication in progress', ]; // Addinfo if (isset($taskStatus['progress'])) 
{
 $result['progress'] = [ 'percentage' => $taskStatus['progress']['percentage'], 'message' => $taskStatus['progress']['message'] ?? '', ]; 
}
 // IfTaskcomplete Return Resultinfo if ($taskStatus['status'] === 'completed' && isset($taskStatus['result'])) 
{
 $topicEntity = $this->topicDomainService->getTopicById($taskStatus['result']['topic_id']); $result['result'] = TopicItemDTO::fromEntity($topicEntity)->toArray(); 
}
 // IfTaskFailedReturn Error message if ($taskStatus['status'] === 'failed' && isset($taskStatus['error'])) 
{
 $result['error'] = $taskStatus['error']; 
}
 return $result; 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to check topic duplication status', [ 'user_id' => $userAuthorization->getId(), 'task_key' => $taskKey, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); throw $e; 
}
 
}
 
    public function downloadChatHistory(RequestContext $requestContext, int $id): array 
{
 return []; 
}
 
}
 
