<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\File\Service\FileAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Constant\TopicDuplicateConstant;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\DeleteDataType;
use Delightful\BeDelightful\Domain\BeAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\TopicCreatedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\TopicDeletedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\TopicRenamedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\TopicUpdatedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileTreeUtil;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\DeleteTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\DuplicateTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetTopicAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\SaveTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\DeleteTopicResultDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\MessageItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\SaveTopicResultDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TopicItemDTO;
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
        protected DelightfulChatMessageAppService $delightfulChatMessageAppService,
        protected FileAppService $fileAppService,
        protected ChatAppService $chatAppService,
        protected Producer $producer,
        protected EventDispatcherInterface $eventDispatcher,
        protected TopicDuplicateStatusManager $topicDuplicateStatusManager,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    public function getTopic(RequestContext $requestContext, int $id): TopicItemDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get topic content
        $topicEntity = $this->topicDomainService->getTopicById($id);
        if (! $topicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        // Determine if topic belongs to self
        if ($topicEntity->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied');
        }

        return TopicItemDTO::fromEntity($topicEntity);
    }

    public function getTopicById(int $id): TopicItemDTO
    {
        // Get topic content
        $topicEntity = $this->topicDomainService->getTopicById($id);
        if (! $topicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }
        return TopicItemDTO::fromEntity($topicEntity);
    }

    public function createTopic(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): TopicItemDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        $projectEntity = $this->getAccessibleProjectWithEditor((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Create new topic，Use transaction to ensure atomicity
        Db::beginTransaction();
        try {
            // 1. Initialize chat conversation and topic
            [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initDelightfulChatConversation($dataIsolation);

            // 2. Create topic
            $topicEntity = $this->topicDomainService->createTopic(
                $dataIsolation,
                $projectEntity->getWorkspaceId(),
                (int) $requestDTO->getProjectId(),
                $chatConversationId,
                $chatConversationTopicId, // Conversation topicID
                $requestDTO->getTopicName(),
                $projectEntity->getWorkDir(),
                $requestDTO->getTopicMode()
            );

            // 3. If passed project_mode，Update project mode
            if (! empty($requestDTO->getProjectMode())) {
                $projectEntity->setProjectMode($requestDTO->getProjectMode());
                $projectEntity->setUpdatedAt(date('Y-m-d H:i:s'));
                $this->projectDomainService->saveProjectEntity($projectEntity);
            }
            // Commit transaction
            Db::commit();

            // PublishTopicAlreadyCreateEvent
            $topicCreatedEvent = new TopicCreatedEvent($topicEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($topicCreatedEvent);

            // Return result
            return TopicItemDTO::fromEntity($topicEntity);
        } catch (Throwable $e) {
            // Rollback transaction
            Db::rollBack();
            $this->logger->error(sprintf("Error creating new topic: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
            ExceptionBuilder::throw(BeAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed');
        }
    }

    public function createTopicNotValidateAccessibleProject(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): ?TopicItemDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $requestDTO->getProjectId());

        // Create new topic，Use transaction to ensure atomicity
        Db::beginTransaction();
        try {
            // 1. Initialize chat conversation and topic
            [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initDelightfulChatConversation($dataIsolation);

            // 2. Create topic
            $topicEntity = $this->topicDomainService->createTopic(
                $dataIsolation,
                (int) $requestDTO->getWorkspaceId(),
                (int) $requestDTO->getProjectId(),
                $chatConversationId,
                $chatConversationTopicId, // Conversation topicID
                $requestDTO->getTopicName(),
                $projectEntity->getWorkDir(),
                $requestDTO->getTopicMode(),
            );

            // 3. If passed project_mode，Update project mode
            if (! empty($requestDTO->getProjectMode())) {
                $projectEntity->setProjectMode($requestDTO->getProjectMode());
                $projectEntity->setUpdatedAt(date('Y-m-d H:i:s'));
                $this->projectDomainService->saveProjectEntity($projectEntity);
            }
            // Commit transaction
            Db::commit();
            // Return result
            return TopicItemDTO::fromEntity($topicEntity);
        } catch (Throwable $e) {
            // Rollback transaction
            Db::rollBack();
            $this->logger->error(sprintf("Error creating new topic: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
            ExceptionBuilder::throw(BeAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed');
        }
    }

    public function updateTopic(RequestContext $requestContext, SaveTopicRequestDTO $requestDTO): SaveTopicResultDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        $this->topicDomainService->updateTopic($dataIsolation, (int) $requestDTO->getId(), $requestDTO->getTopicName());

        // Get updated topic entity for event publishing
        $topicEntity = $this->topicDomainService->getTopicById((int) $requestDTO->getId());

        // PublishTopicAlreadyUpdateEvent
        if ($topicEntity) {
            $topicUpdatedEvent = new TopicUpdatedEvent($topicEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($topicUpdatedEvent);
        }

        return SaveTopicResultDTO::fromId((int) $requestDTO->getId());
    }

    public function renameTopic(DelightfulUserAuthorization $authorization, int $topicId, string $userQuestion, string $language = 'zh_CN'): array
    {
        // Get topic content
        $topicEntity = $this->workspaceDomainService->getTopicById($topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        // Call domain service to execute rename(This step withdelightful-serviceBinding)
        try {
            $text = $this->delightfulChatMessageAppService->summarizeText($authorization, $userQuestion, $language);
            // UpdateTopicName
            $dataIsolation = $this->createDataIsolation($authorization);
            $this->topicDomainService->updateTopicName($dataIsolation, $topicId, $text);

            // Get updated topic entity and publish rename event
            $updatedTopicEntity = $this->topicDomainService->getTopicById($topicId);
            if ($updatedTopicEntity) {
                $topicRenamedEvent = new TopicRenamedEvent($updatedTopicEntity, $authorization);
                $this->eventDispatcher->dispatch($topicRenamedEvent);
            }
        } catch (Exception $e) {
            $this->logger->error('rename topic error: ' . $e->getMessage());
            $text = $topicEntity->getTopicName();
        }

        return ['topic_name' => $text];
    }

    /**
     * DeleteTopic.
     *
     * @param RequestContext $requestContext Request context
     * @param DeleteTopicRequestDTO $requestDTO RequestDTO
     * @return DeleteTopicResultDTO Deletion result
     * @throws BusinessException|Exception If user has no permission,Topicdoes not exist or task is running
     */
    public function deleteTopic(RequestContext $requestContext, DeleteTopicRequestDTO $requestDTO): DeleteTopicResultDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // GetTopicID
        $topicId = $requestDTO->getId();

        // First get topic entity for event publishing
        $topicEntity = $this->topicDomainService->getTopicById((int) $topicId);

        // Call domain service to execute deletion
        $result = $this->topicDomainService->deleteTopic($dataIsolation, (int) $topicId);

        // Deliver event，Stop service
        if ($result) {
            // PublishTopicAlreadyDeleteEvent
            if ($topicEntity) {
                $topicDeletedEvent = new TopicDeletedEvent($topicEntity, $userAuthorization);
                $this->eventDispatcher->dispatch($topicDeletedEvent);
            }

            $event = new StopRunningTaskEvent(
                DeleteDataType::TOPIC,
                (int) $topicId,
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                'Topic already deleted'
            );
            $publisher = new StopRunningTaskPublisher($event);
            $this->producer->produce($publisher);
        }

        // If deletion fails，Throw exception
        if (! $result) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'topic.delete_failed');
        }

        // ReturnDeletion result
        return DeleteTopicResultDTO::fromId((int) $topicId);
    }

    /**
     * Get topics with recent update time exceeding specified time.
     *
     * @param string $timeThreshold Time threshold, if topic update time is before this time, will be included in result
     * @param int $limit Maximum number of results returned
     * @return array<TopicEntity> Topic entity list
     */
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
        $token = $requestDto->getToken();
        // Get data from cache
        if (! AccessTokenUtil::validate($token)) {
            ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'task_file.access_denied');
        }
        // Get content from token
        $shareId = AccessTokenUtil::getShareId($token);
        $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found');
        }
        if ($shareEntity->getResourceType() != ResourceType::Topic->value) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported');
        }
        $organizationCode = AccessTokenUtil::getOrganizationCode($token);
        $requestDto->setTopicId($shareEntity->getResourceId());

        // CreateDataIsolation
        $dataIsolation = DataIsolation::simpleMake($organizationCode, '');
        return $this->getTopicAttachmentList($dataIsolation, $requestDto);
    }

    public function getTopicAttachmentList(DataIsolation $dataIsolation, GetTopicAttachmentsRequestDTO $requestDto): array
    {
        // DetermineTopicWhether exists
        $topicEntity = $this->topicDomainService->getTopicById((int) $requestDto->getTopicId());
        if (empty($topicEntity)) {
            return [];
        }

        $projectEntity = $this->projectDomainService->getProjectNotUserId($topicEntity->getProjectId());

        $sandboxId = $topicEntity->getSandboxId();
        $workDir = $topicEntity->getWorkDir();

        // Get topic attachment list through domain service
        $result = $this->taskDomainService->getTaskAttachmentsByTopicId(
            (int) $requestDto->getTopicId(),
            $dataIsolation,
            $requestDto->getPage(),
            $requestDto->getPageSize(),
            $requestDto->getFileType()
        );

        // ProcessFile URL
        $list = [];
        $projectOrganizationCode = $projectEntity->getUserOrganizationCode();

        // Traverse attachment list, use TaskFileItemDTO to process
        foreach ($result['list'] as $entity) {
            // CreateDTO
            $dto = new TaskFileItemDTO();
            $dto->fileId = (string) $entity->getFileId();
            $dto->taskId = (string) $entity->getTaskId();
            $dto->fileType = $entity->getFileType();
            $dto->fileName = $entity->getFileName();
            $dto->fileExtension = $entity->getFileExtension();
            $dto->fileKey = $entity->getFileKey();
            $dto->fileSize = $entity->getFileSize();
            $dto->isHidden = $entity->getIsHidden();
            $dto->topicId = (string) $entity->getTopicId();

            // Calculate relative file path by removing workDir from fileKey
            $fileKey = $entity->getFileKey();
            $workDirPos = strpos($fileKey, $workDir);
            if ($workDirPos !== false) {
                $dto->relativeFilePath = substr($fileKey, $workDirPos + strlen($workDir));
            } else {
                $dto->relativeFilePath = $fileKey; // If workDir not found, use original fileKey
            }

            // Add file_url Field
            $fileKey = $entity->getFileKey();
            if (! empty($fileKey)) {
                $fileLink = $this->fileAppService->getLink($projectOrganizationCode, $fileKey, StorageBucketType::SandBox);
                if ($fileLink) {
                    $dto->fileUrl = $fileLink->getUrl();
                } else {
                    $dto->fileUrl = '';
                }
            } else {
                $dto->fileUrl = '';
            }

            $list[] = $dto->toArray();
        }

        // Build tree structure
        $tree = FileTreeUtil::assembleFilesTree($list);

        return [
            'list' => $list,
            'tree' => $tree,
            'total' => $result['total'],
        ];
    }

    /**
     * GetTopicofAttachmentlist.(For admin backend use).
     *
     * @param DelightfulUserAuthorization $userAuthorization User authorization information
     * @param GetTopicAttachmentsRequestDTO $requestDto TopicAttachmentRequestDTO
     * @return array Attachmentlist
     */
    public function getTopicAttachments(DelightfulUserAuthorization $userAuthorization, GetTopicAttachmentsRequestDTO $requestDto): array
    {
        // Get current topic creator
        $topicEntity = $this->topicDomainService->getTopicById((int) $requestDto->getTopicId());
        if ($topicEntity === null) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }
        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->getTopicAttachmentList($dataIsolation, $requestDto);
    }

    /**
     * Get user topic message list.
     *
     * @param DelightfulUserAuthorization $userAuthorization User authorization information
     * @param int $topicId TopicID
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $sortDirection Sort direction
     * @return array Message list and total count
     */
    public function getUserTopicMessage(DelightfulUserAuthorization $userAuthorization, int $topicId, int $page, int $pageSize, string $sortDirection): array
    {
        // Get message list
        $result = $this->taskDomainService->getMessagesByTopicId($topicId, $page, $pageSize, true, $sortDirection);

        // Convert to response format
        $messages = [];
        foreach ($result['list'] as $message) {
            $messages[] = new MessageItemDTO($message->toArray());
        }

        return [
            'list' => $messages,
            'total' => $result['total'],
        ];
    }

    /**
     * Get user topic attachment URL. (For admin backend use).
     *
     * @param string $topicId Topic ID
     * @param DelightfulUserAuthorization $userAuthorization User authorization information
     * @param array $fileIds FileIDlist
     * @return array Array containing attachment URLs
     */
    public function getTopicAttachmentUrl(DelightfulUserAuthorization $userAuthorization, string $topicId, array $fileIds, string $downloadMode): array
    {
        $result = [];
        foreach ($fileIds as $fileId) {
            // Get file entity
            $fileEntity = $this->taskDomainService->getTaskFile((int) $fileId);
            if (empty($fileEntity)) {
                // If file does not exist, skip
                continue;
            }
            $downloadNames = [];
            if ($downloadMode == 'download') {
                $downloadNames[$fileEntity->getFileKey()] = $fileEntity->getFileName();
            }

            $fileLink = $this->fileAppService->getLink($fileEntity->getOrganizationCode(), $fileEntity->getFileKey(), StorageBucketType::SandBox, $downloadNames);
            if (empty($fileLink)) {
                // If getting link fails, skip
                continue;
            }

            $result[] = [
                'file_id' => (string) $fileEntity->getFileId(),
                'url' => $fileLink->getUrl(),
            ];
        }
        return $result;
    }

    /**
     * Duplicate topic (synchronous) - blocks until completion.
     * This method performs complete topic duplication synchronously without task management.
     *
     * @param RequestContext $requestContext Request context
     * @param string $sourceTopicId Source topic ID
     * @param DuplicateTopicRequestDTO $requestDTO Request DTO
     * @return array Complete result with topic info
     * @throws BusinessException If validation fails or operation fails
     */
    public function duplicateTopic(RequestContext $requestContext, string $sourceTopicId, DuplicateTopicRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        $this->logger->info('Topic duplication sync started', [
            'user_id' => $userAuthorization->getId(),
            'source_topic_id' => $sourceTopicId,
            'target_message_id' => $requestDTO->getTargetMessageId(),
            'new_topic_name' => $requestDTO->getNewTopicName(),
        ]);

        // Validate topic and permissions
        $sourceTopicEntity = $this->topicDomainService->getTopicById((int) $sourceTopicId);
        if (! $sourceTopicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        if ($sourceTopicEntity->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied');
        }

        // Create data isolation
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Execute complete duplication in transaction
        Db::beginTransaction();
        try {
            // Call domain service - pure business logic
            $newTopicEntity = $this->topicDomainService->duplicateTopic(
                $dataIsolation,
                $sourceTopicEntity,
                $requestDTO->getNewTopicName(),
                (int) $requestDTO->getTargetMessageId()
            );

            Db::commit();

            $this->logger->info('Topic duplication sync completed', [
                'source_topic_id' => $sourceTopicId,
                'new_topic_id' => $newTopicEntity->getId(),
            ]);

            // Return complete result
            return [
                'status' => 'completed',
                'message' => 'Topic duplicated successfully',
                'topic' => TopicItemDTO::fromEntity($newTopicEntity)->toArray(),
            ];
        } catch (Throwable $e) {
            Db::rollBack();

            $this->logger->error('Topic duplication sync failed', [
                'source_topic_id' => $sourceTopicId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Duplicate topic (asynchronous) - returns immediately with task_id.
     * This method creates topic skeleton synchronously, then copies messages asynchronously.
     *
     * @param RequestContext $requestContext Request context
     * @param string $sourceTopicId Source topic ID
     * @param DuplicateTopicRequestDTO $requestDTO Request DTO
     * @return array Task info with task_id
     * @throws BusinessException If validation fails or operation fails
     */
    public function duplicateChatAsync(RequestContext $requestContext, string $sourceTopicId, DuplicateTopicRequestDTO $requestDTO): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        $this->logger->info('Starting topic duplication async (skeleton sync + message copy async)', [
            'user_id' => $userAuthorization->getId(),
            'source_topic_id' => $sourceTopicId,
            'target_message_id' => $requestDTO->getTargetMessageId(),
            'new_topic_name' => $requestDTO->getNewTopicName(),
        ]);

        // VerifyTopicExistence and permissions
        $sourceTopicEntity = $this->topicDomainService->getTopicById((int) $sourceTopicId);
        if (! $sourceTopicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        // Determine if topic belongs to self
        if ($sourceTopicEntity->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.access_denied');
        }

        // === Synchronization part:Create topicSkeleton ===
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // In transactionCreate topicSkeleton
        Db::beginTransaction();
        try {
            // Call domain layer to create topic skeleton (includes IM session)
            $duplicateResult = $this->topicDomainService->duplicateTopicSkeleton(
                $dataIsolation,
                $sourceTopicEntity,
                $requestDTO->getNewTopicName()
            );

            $newTopicEntity = $duplicateResult['topic_entity'];
            $imConversationResult = $duplicateResult['im_conversation'];

            // Commit transaction
            Db::commit();

            $this->logger->info('Topic skeleton created successfully (sync)', [
                'source_topic_id' => $sourceTopicId,
                'new_topic_id' => $newTopicEntity->getId(),
            ]);
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error('Failed to create topic skeleton (sync)', [
                'source_topic_id' => $sourceTopicId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Convert topic entity to DTO
        $topicItemDTO = TopicItemDTO::fromEntity($newTopicEntity);

        // Generate task key
        $taskKey = TopicDuplicateConstant::generateTaskKey($sourceTopicId, $userAuthorization->getId());

        // Initialize async task
        $taskData = [
            'source_topic_id' => $sourceTopicId,
            'target_message_id' => $requestDTO->getTargetMessageId(),
            'new_topic_name' => $requestDTO->getNewTopicName(),
            'user_id' => $userAuthorization->getId(),
            'new_topic_id' => $newTopicEntity->getId(), // Save new topic ID
            'im_conversation' => $imConversationResult, // Save IM session information
        ];

        $this->topicDuplicateStatusManager->initializeTask($taskKey, $userAuthorization->getId(), $taskData);

        // Get current request ID
        $requestId = CoContext::getRequestId() ?: (string) IdGenerator::getSnowId();

        // === Async part: copy messages ===
        go(function () use ($sourceTopicEntity, $newTopicEntity, $requestDTO, $imConversationResult, $taskKey, $requestId) {
            // Copy request context
            CoContext::setRequestId($requestId);

            try {
                // Update task status: start copying messages
                $this->topicDuplicateStatusManager->setTaskProgress($taskKey, 10, 'Starting to copy messages');

                // Start database transaction
                Db::beginTransaction();
                try {
                    // Execute message copy logic
                    $this->topicDomainService->copyTopicMessageFromOthers(
                        $sourceTopicEntity,
                        $newTopicEntity,
                        (int) $requestDTO->getTargetMessageId(),
                        $imConversationResult,
                        // Progress callback function
                        function (string $status, int $progress, string $message) use ($taskKey) {
                            $this->topicDuplicateStatusManager->setTaskProgress($taskKey, $progress, $message);
                        }
                    );

                    // Commit transaction
                    Db::commit();

                    // Task complete
                    $this->topicDuplicateStatusManager->setTaskCompleted($taskKey, [
                        'topic_id' => $newTopicEntity->getId(),
                        'topic_name' => $newTopicEntity->getTopicName(),
                        'project_id' => $newTopicEntity->getProjectId(),
                        'workspace_id' => $newTopicEntity->getWorkspaceId(),
                    ]);

                    $this->logger->info('Topic message copy completed successfully (async)', [
                        'task_key' => $taskKey,
                        'source_topic_id' => $sourceTopicEntity->getId(),
                        'new_topic_id' => $newTopicEntity->getId(),
                    ]);
                } catch (Throwable $e) {
                    // Rollback transaction
                    Db::rollBack();
                    throw $e; // Re-throw exception, let outer catch process
                }
            } catch (Throwable $e) {
                // Task failed
                $this->topicDuplicateStatusManager->setTaskFailed($taskKey, $e->getMessage());

                $this->logger->error('Async topic message copy failed', [
                    'task_key' => $taskKey,
                    'source_topic_id' => $sourceTopicEntity->getId(),
                    'new_topic_id' => $newTopicEntity->getId(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        // Immediately return task information and newly created topic
        return [
            'task_id' => $taskKey,
            'status' => 'copying',
            'message' => 'Topic created, copying messages in background',
            'topic' => $topicItemDTO->toArray(), // New: immediately return topic information
        ];
    }

    /**
     * Check topic copy status
     *
     * @param RequestContext $requestContext Request context
     * @param string $taskKey Task key
     * @return array Copy status information
     * @throws BusinessException If parameter invalid or operation fails then throw exception
     */
    public function checkDuplicateChatStatus(RequestContext $requestContext, string $taskKey): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        $this->logger->info('Checking topic duplication status', [
            'user_id' => $userAuthorization->getId(),
            'task_key' => $taskKey,
        ]);

        try {
            // Verify user permissions
            if (! $this->topicDuplicateStatusManager->verifyUserPermission($taskKey, $userAuthorization->getId())) {
                ExceptionBuilder::throw(BeAgentErrorCode::TASK_ACCESS_DENIED, 'Task access denied');
            }

            // Get task status
            $taskStatus = $this->topicDuplicateStatusManager->getTaskStatus($taskKey);
            if (! $taskStatus) {
                ExceptionBuilder::throw(BeAgentErrorCode::TASK_NOT_FOUND, 'Task not found or expired');
            }

            // Build return result
            $result = [
                'task_id' => $taskKey,
                'status' => $taskStatus['status'], // running, completed, failed
                'message' => $taskStatus['message'] ?? 'Topic duplication in progress',
            ];

            // Add progress information
            if (isset($taskStatus['progress'])) {
                $result['progress'] = [
                    'percentage' => $taskStatus['progress']['percentage'],
                    'message' => $taskStatus['progress']['message'] ?? '',
                ];
            }

            // If task complete, return result information
            if ($taskStatus['status'] === 'completed' && isset($taskStatus['result'])) {
                $topicEntity = $this->topicDomainService->getTopicById($taskStatus['result']['topic_id']);
                $result['result'] = TopicItemDTO::fromEntity($topicEntity)->toArray();
            }

            // If task failed, return error information
            if ($taskStatus['status'] === 'failed' && isset($taskStatus['error'])) {
                $result['error'] = $taskStatus['error'];
            }

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('Failed to check topic duplication status', [
                'user_id' => $userAuthorization->getId(),
                'task_key' => $taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function downloadChatHistory(RequestContext $requestContext, int $id): array
    {
        return [];
    }
}
