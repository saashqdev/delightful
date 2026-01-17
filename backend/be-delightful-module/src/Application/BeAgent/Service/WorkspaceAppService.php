<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\File\Service\FileAppService;
use App\Application\File\Service\FileCleanupAppService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Chat\Service\DelightfulTopicDomainService as DelightfulChatTopicDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\LongTermMemory\Service\LongTermMemoryDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\CreationSource;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\DeleteDataType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Delightful\BeDelightful\Domain\BeAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Volcengine\SandboxService;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetWorkspaceTopicsRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\SaveWorkspaceRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\WorkspaceListRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\MessageItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\SaveWorkspaceResultDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TopicListResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\WorkspaceItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\WorkspaceListResponseDTO;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class WorkspaceAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        protected DelightfulChatMessageAppService $delightfulChatMessageAppService,
        protected DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        protected WorkspaceDomainService $workspaceDomainService,
        protected DelightfulConversationDomainService $delightfulConversationDomainService,
        protected DelightfulUserDomainService $userDomainService,
        protected DelightfulChatTopicDomainService $delightfulTopicDomainService,
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
        protected LongTermMemoryDomainService $longTermMemoryDomainService
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * GetWorkspaceList.
     */
    public function getWorkspaceList(RequestContext $requestContext, WorkspaceListRequestDTO $requestDTO): WorkspaceListResponseDTO
    {
        // Build query conditions
        $conditions = $requestDTO->buildConditions();

        // If no user specifiedIDand has user authorization information，Use current userID
        if (empty($conditions['user_id'])) {
            $conditions['user_id'] = $requestContext->getUserAuthorization()->getId();
        }

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());

        // Get workspace list through domain service
        $result = $this->workspaceDomainService->getWorkspacesByConditions(
            $conditions,
            $requestDTO->page,
            $requestDTO->pageSize,
            'id',
            'desc',
            $dataIsolation
        );

        // Set default values
        $result['auto_create'] = false;

        if (empty($result['list'])) {
            $workspaceEntity = $this->workspaceDomainService->createWorkspace(
                $dataIsolation,
                '',
                ''
            );
            $result['list'] = [$workspaceEntity->toArray()];
            $result['total'] = 1;
            $result['auto_create'] = true;
        }

        // Extract all Workspace IDs
        $workspaceIds = [];
        foreach ($result['list'] as $workspace) {
            if (is_array($workspace)) {
                $workspaceIds[] = $workspace['id'];
            } else {
                $workspaceIds[] = $workspace->getId();
            }
        }
        $workspaceIds = array_unique($workspaceIds);

        // Batch get workspace status
        $currentUserId = $dataIsolation->getCurrentUserId();
        $workspaceStatusMap = $this->topicDomainService->calculateWorkspaceStatusBatch($workspaceIds, $currentUserId);

        // Convert to responseDTOand pass status mapping
        return WorkspaceListResponseDTO::fromResult($result, $workspaceStatusMap);
    }

    /**
     * GetWorkspaceDetails.
     */
    public function getWorkspaceDetail(RequestContext $requestContext, int $workspaceId): WorkspaceItemDTO
    {
        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());

        // GetWorkspaceDetails
        $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($workspaceId);
        if ($workspaceEntity === null) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found');
        }

        // VerifyWorkspaceWhether belongs to current user
        if ($workspaceEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_ACCESS_DENIED, 'workspace.access_denied');
        }

        // CalculateWorkspaceStatus
        $workspaceStatusMap = $this->topicDomainService->calculateWorkspaceStatusBatch([$workspaceId]);
        $workspaceStatus = $workspaceStatusMap[$workspaceId] ?? null;

        // ReturnWorkspaceDetailsDTO
        return WorkspaceItemDTO::fromEntity($workspaceEntity, $workspaceStatus);
    }

    public function createWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        $workspaceEntity = $this->workspaceDomainService->createWorkspace(
            $dataIsolation,
            '',
            $requestDTO->getWorkspaceName()
        );

        return SaveWorkspaceResultDTO::fromId((int) $workspaceEntity->getId());
    }

    public function updateWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        if (empty($requestDTO->getWorkspaceId())) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND);
        }

        $this->workspaceDomainService->updateWorkspace($dataIsolation, (int) $requestDTO->getWorkspaceId(), $requestDTO->getWorkspaceName());

        return SaveWorkspaceResultDTO::fromId((int) $requestDTO->getWorkspaceId());
    }

    /**
     * Save workspace (create or update).
     * @return SaveWorkspaceResultDTO Operation result, including workspace ID
     * @throws BusinessException Throws an exception if saving fails
     * @throws Throwable
     */
    public function saveWorkspace(RequestContext $requestContext, SaveWorkspaceRequestDTO $requestDTO): SaveWorkspaceResultDTO
    {
        Db::beginTransaction();
        try {
            // Get user authorization information
            $userAuthorization = $requestContext->getUserAuthorization();

            // Create data isolation object
            $dataIsolation = $this->createDataIsolation($userAuthorization);

            // Prepare workspace entity
            if ($requestDTO->getWorkspaceId()) {
                // Update, currently only updates workspace name
                $this->workspaceDomainService->updateWorkspace($dataIsolation, (int) $requestDTO->getWorkspaceId(), $requestDTO->getWorkspaceName());
                Db::commit();
                return SaveWorkspaceResultDTO::fromId((int) $requestDTO->getWorkspaceId());
            }

            // Commit transaction
            Db::commit();

            // Create, use provided workspace name if available; otherwise use default name
            $result = $this->initUserWorkspace($dataIsolation, $requestDTO->getWorkspaceName());
            return SaveWorkspaceResultDTO::fromId($result['workspace']->getId());
        } catch (EventException $e) {
            // Rollback transaction
            Db::rollBack();
            $this->logger->error(sprintf("Error creating new workspace event: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
            ExceptionBuilder::throw(BeAgentErrorCode::CREATE_TOPIC_FAILED, $e->getMessage());
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error(sprintf("Error creating new workspace: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
            ExceptionBuilder::throw(BeAgentErrorCode::CREATE_TOPIC_FAILED, 'topic.create_topic_failed');
        }
    }

    /**
     * GetWorkspaceunderTopicList.
     */
    public function getWorkspaceTopics(RequestContext $requestContext, GetWorkspaceTopicsRequestDTO $dto): TopicListResponseDTO
    {
        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());

        // Get workspace topic list through domain service
        $result = $this->workspaceDomainService->getWorkspaceTopics(
            [$dto->getWorkspaceId()],
            $dataIsolation,
            true,
            $dto->getPageSize(),
            $dto->getPage(),
            $dto->getOrderBy(),
            $dto->getOrderDirection()
        );

        // Convert to response DTO
        return TopicListResponseDTO::fromResult($result);
    }

    /**
     * Get task attachment list.
     */
    public function getTaskAttachments(DelightfulUserAuthorization $userAuthorization, int $taskId, int $page = 1, int $pageSize = 10): array
    {
        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get task attachment list
        $result = $this->workspaceDomainService->getTaskAttachments($taskId, $dataIsolation, $page, $pageSize);

        // ProcessFile URL
        $list = [];
        $organizationCode = $userAuthorization->getOrganizationCode();
        $fileKeys = [];
        // Traverse attachmentList，UseTaskFileItemDTOProcess
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
            $dto->topicId = (string) $entity->getTopicId();

            // Add file_url Field
            $fileKey = $entity->getFileKey();
            if (! empty($fileKey)) {
                $fileLink = $this->fileAppService->getLink($organizationCode, $fileKey, StorageBucketType::SandBox);
                if ($fileLink) {
                    $dto->fileUrl = $fileLink->getUrl();
                } else {
                    $dto->fileUrl = '';
                }
            } else {
                $dto->fileUrl = '';
            }
            // DeterminefilekeyWhether duplicate，If duplicate，ThenSkip
            if (in_array($fileKey, $fileKeys)) {
                continue;
            }
            $fileKeys[] = $fileKey;
            $list[] = $dto->toArray();
        }

        return [
            'list' => $list,
            'total' => $result['total'],
        ];
    }

    /**
     * DeleteWorkspace.
     *
     * @param RequestContext $requestContext Request context
     * @param int $workspaceId WorkspaceID
     * @return bool Whether deletion is successful
     * @throws BusinessException If user has no permission or workspace does not exist then throw exception
     */
    public function deleteWorkspace(RequestContext $requestContext, int $workspaceId): bool
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Call domain service to execute deletion
        Db::beginTransaction();
        try {
            // First get all project IDs under workspace, for deleting long-term memory
            $projectIds = $this->projectDomainService->getProjectIdsByWorkspaceId($dataIsolation, $workspaceId);

            // Batch delete project-related long-term memory
            if (! empty($projectIds)) {
                $this->longTermMemoryDomainService->deleteMemoriesByProjectIds(
                    $dataIsolation->getCurrentOrganizationCode(),
                    AgentConstant::BE_DELIGHTFUL_CODE,
                    $dataIsolation->getCurrentUserId(),
                    $projectIds
                );
            }

            // DeleteWorkspace
            $this->workspaceDomainService->deleteWorkspace($dataIsolation, $workspaceId);

            // Delete projects under workspace
            $this->projectDomainService->deleteProjectsByWorkspaceId($dataIsolation, $workspaceId);

            // Delete workspace topics
            $this->topicDomainService->deleteTopicsByWorkspaceId($dataIsolation, $workspaceId);

            // Deliver message，Stop all running tasks
            $event = new StopRunningTaskEvent(
                DeleteDataType::WORKSPACE,
                $workspaceId,
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                'Workspace already deleted'
            );
            $publisher = new StopRunningTaskPublisher($event);
            $this->producer->produce($publisher);

            $this->logger->info(sprintf(
                'Stop task message already delivered，WorkspaceID: %d, EventID: %s',
                $workspaceId,
                $event->getEventId()
            ));

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error('DeleteWorkspaceFailed:' . $e->getMessage());
            throw $e;
        }

        return true;
    }

    /**
     * Get task details.
     *
     * @param RequestContext $requestContext Request context
     * @param int $taskId TaskID
     * @return array TaskDetails
     * @throws BusinessException If user has no permission or task does not exist then throw exception
     */
    public function getTaskDetail(RequestContext $requestContext, int $taskId): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get task details
        $taskEntity = $this->workspaceDomainService->getTaskById($taskId);
        if (! $taskEntity) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'task.not_found');
        }

        return $taskEntity->toArray();
    }

    /**
     * GetTopicmessageList.
     *
     * @param int $topicId TopicID
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $sortDirection Sort direction，Supportascanddesc
     * @return array MessageListandTotal count
     */
    public function getMessagesByTopicId(int $topicId, int $page = 1, int $pageSize = 20, string $sortDirection = 'asc'): array
    {
        // GetMessageList
        $result = $this->taskDomainService->getMessagesByTopicId($topicId, $page, $pageSize, true, $sortDirection);

        // Convert to responseFormat
        $messages = [];
        foreach ($result['list'] as $message) {
            $messages[] = new MessageItemDTO($message->toArray());
        }

        $data = [
            'list' => $messages,
            'total' => $result['total'],
        ];

        // Get topic Information
        $topicEntity = $this->topicDomainService->getTopicWithDeleted($topicId);
        if ($topicEntity != null) {
            $data['project_id'] = (string) $topicEntity->getProjectId();
            $projectEntity = $this->getAccessibleProject($topicEntity->getProjectId(), $topicEntity->getUserId(), $topicEntity->getUserOrganizationCode());
            $data['project_name'] = $projectEntity->getProjectName();
        }
        return $data;
    }

    /**
     * SetWorkspaceArchive status.
     *
     * @param RequestContext $requestContext Request context
     * @param array $workspaceIds WorkspaceIDArray
     * @param int $isArchived Archive status(0:Not archived, 1:Already archived)
     * @return bool Whether operation is successful
     */
    public function setWorkspaceArchived(RequestContext $requestContext, array $workspaceIds, int $isArchived): bool
    {
        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());
        $currentUserId = $dataIsolation->getCurrentUserId();

        // Parameter verification
        if (empty($workspaceIds)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'workspace.ids_required');
        }

        // Verify if archive status value is valid
        if (! in_array($isArchived, [
            WorkspaceArchiveStatus::NotArchived->value,
            WorkspaceArchiveStatus::Archived->value,
        ])) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.invalid_archive_status');
        }

        // Batch updateWorkspaceArchive status
        $success = true;
        foreach ($workspaceIds as $workspaceId) {
            // Get workspace details, verify ownership
            $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail((int) $workspaceId);

            // IfWorkspaceDoes not exist，Skip
            if (! $workspaceEntity) {
                $success = false;
                continue;
            }

            // VerifyWorkspaceWhether belongs to current user
            if ($workspaceEntity->getUserId() !== $currentUserId) {
                ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'workspace.not_owner');
            }

            // Call domain service to set archive status
            $result = $this->workspaceDomainService->archiveWorkspace(
                $requestContext,
                (int) $workspaceId,
                $isArchived === WorkspaceArchiveStatus::Archived->value
            );
            if (! $result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * GetFileURLList.
     *
     * @param DelightfulUserAuthorization $userAuthorization User authorization information
     * @param array $fileIds File ID list
     * @param string $downloadMode Download mode (download: download, preview: preview)
     * @param array $options Other options
     * @return array FileURLList
     */
    public function getFileUrls(DelightfulUserAuthorization $userAuthorization, array $fileIds, string $downloadMode, array $options = []): array
    {
        // Create data isolation object
        $organizationCode = $userAuthorization->getOrganizationCode();
        $result = [];

        foreach ($fileIds as $fileId) {
            // Get file entity
            $fileEntity = $this->taskDomainService->getTaskFile((int) $fileId);
            if (empty($fileEntity)) {
                // IfFileDoes not exist，Skip
                continue;
            }

            // VerifyFileWhether belongs to current user
            $projectEntity = $this->getAccessibleProject($fileEntity->getProjectId(), $userAuthorization->getId(), $organizationCode);

            $downloadNames = [];
            if ($downloadMode === 'download') {
                $downloadNames[$fileEntity->getFileKey()] = $fileEntity->getFileName();
            }
            $fileLink = $this->fileAppService->getLink($organizationCode, $fileEntity->getFileKey(), StorageBucketType::SandBox, $downloadNames, $options);
            if (empty($fileLink)) {
                // IfGetURLFailed，Skip
                continue;
            }

            // Only add successful results
            $result[] = [
                'file_id' => $fileId,
                'url' => $fileLink->getUrl(),
            ];
        }

        return $result;
    }

    public function getTopicDetail(int $topicId): string
    {
        $topicEntity = $this->workspaceDomainService->getTopicById($topicId);
        if (empty($topicEntity)) {
            return '';
        }
        return $topicEntity->getTopicName();
    }

    /**
     * Get workspace information through topic ID collection.
     *
     * @param array $topicIds Topic ID collection (string array)
     * @return array Associative array with topic ID as key and workspace information as value
     */
    public function getWorkspaceInfoByTopicIds(array $topicIds): array
    {
        // Convert string ID to integer
        $intTopicIds = array_map('intval', $topicIds);

        // Call domain service to get workspace information
        return $this->workspaceDomainService->getWorkspaceInfoByTopicIds($intTopicIds);
    }

    /**
     * Register converted PDF file for scheduled cleanup.
     */
    public function registerConvertedPdfsForCleanup(DelightfulUserAuthorization $userAuthorization, array $convertedFiles): void
    {
        if (empty($convertedFiles)) {
            return;
        }

        $filesForCleanup = [];
        foreach ($convertedFiles as $file) {
            if (empty($file['oss_key']) || empty($file['filename'])) {
                continue;
            }

            $filesForCleanup[] = [
                'organization_code' => $userAuthorization->getOrganizationCode(),
                'file_key' => $file['oss_key'],
                'file_name' => $file['filename'],
                'file_size' => $file['size'] ?? 0, // If response does not have size, default is 0
                'source_type' => 'pdf_conversion',
                'source_id' => $file['batch_id'] ?? null,
                'expire_after_seconds' => 7200, // Expires after 2 hours
                'bucket_type' => 'private',
            ];
        }

        if (! empty($filesForCleanup)) {
            $this->fileCleanupAppService->registerFilesForCleanup($filesForCleanup);
            $this->logger->info('[PDF Converter] Registered converted PDF files for cleanup', [
                'user_id' => $userAuthorization->getId(),
                'files_count' => count($filesForCleanup),
            ]);
        }
    }

    /**
     * Initialize user workspace.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param string $workspaceName Workspace name, default is "My Workspace"
     * @return array Creation result, includes workspace and topic entity objects, and auto_create flag
     * @throws BusinessException If creation failed then throw exception
     * @throws Throwable
     */
    private function initUserWorkspace(
        DataIsolation $dataIsolation,
        string $workspaceName = ''
    ): array {
        $this->logger->info('Start initializing user workspace');
        Db::beginTransaction();
        try {
            // Step 1: Initialize Delightful Chat Conversation
            [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initDelightfulChatConversation($dataIsolation);
            $this->logger->info(sprintf('Initialize super Maggie, chatConversationId=%s, chatConversationTopicId=%s', $chatConversationId, $chatConversationTopicId));

            // Step 2: Create workspace
            $this->logger->info('StartCreateDefaultWorkspace');
            $workspaceEntity = $this->workspaceDomainService->createWorkspace(
                $dataIsolation,
                $chatConversationId,
                $workspaceName
            );
            $this->logger->info(sprintf('CreateDefaultWorkspaceSuccess, workspaceId=%s', $workspaceEntity->getId()));
            if (! $workspaceEntity->getId()) {
                ExceptionBuilder::throw(GenericErrorCode::SystemError, 'workspace.create_workspace_failed');
            }

            // Create default project
            $this->logger->info('Start creating default project');
            $projectEntity = $this->projectDomainService->createProject(
                $workspaceEntity->getId(),
                '',
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                '',
                '',
                null,
                CreationSource::USER_CREATED->value
            );
            $this->logger->info(sprintf('Create default project success, projectId=%s', $projectEntity->getId()));
            // Get workspace directory
            $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getCurrentUserId(), $projectEntity->getId());

            // Step 4: Create default topic
            $this->logger->info('StartCreateDefaultTopic');
            $topicEntity = $this->topicDomainService->createTopic(
                $dataIsolation,
                $workspaceEntity->getId(),
                $projectEntity->getId(),
                $chatConversationId,
                $chatConversationTopicId,
                '',
                $workDir
            );
            $this->logger->info(sprintf('CreateDefaultTopicSuccess, topicId=%s', $topicEntity->getId()));

            // Step 5: Update workspace current topic
            if ($topicEntity->getId()) {
                // SetWorkspaceInformation
                $workspaceEntity->setCurrentTopicId($topicEntity->getId());
                $workspaceEntity->setCurrentProjectId($projectEntity->getId());
                $this->workspaceDomainService->saveWorkspaceEntity($workspaceEntity);
                $this->logger->info(sprintf('Workspace %s has set current topic %s', $workspaceEntity->getId(), $topicEntity->getId()));
                // Set project information
                $projectEntity->setCurrentTopicId($topicEntity->getId());
                $projectEntity->setWorkspaceId($workspaceEntity->getId());
                $projectEntity->setWorkDir($workDir);
                $this->projectDomainService->saveProjectEntity($projectEntity);
                $this->logger->info(sprintf('Project %s has set current topic %s', $projectEntity->getId(), $topicEntity->getId()));
            }
            Db::commit();

            // Return creation result
            return [
                'workspace' => $workspaceEntity,
                'topic' => $topicEntity,
                'project' => $projectEntity,
                'auto_create' => true,
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
