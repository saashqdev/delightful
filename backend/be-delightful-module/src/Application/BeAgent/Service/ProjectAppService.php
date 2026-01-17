<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\LongTermMemory\Service\LongTermMemoryDomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\ProjectForkPublisher;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectForkEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\DeleteDataType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Event\ForkProjectStartEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectCreatedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectDeletedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectForkEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectUpdatedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileMetadataUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileTreeUtil;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CreateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\ForkProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetProjectAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetProjectAttachmentsV2RequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetProjectListRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\MoveProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\UpdateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ForkProjectResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ForkStatusResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ProjectItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ProjectListResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TopicItemDTO;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * Project Application Service
 */
class ProjectAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        private readonly WorkspaceDomainService $workspaceDomainService,
        private readonly ProjectDomainService $projectDomainService,
        private readonly ProjectMemberDomainService $projectMemberDomainService,
        private readonly TopicDomainService $topicDomainService,
        private readonly TaskDomainService $taskDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
        private readonly ChatAppService $chatAppService,
        private readonly ResourceShareDomainService $resourceShareDomainService,
        private readonly LongTermMemoryDomainService $longTermMemoryDomainService,
        private readonly Producer $producer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PackageFilterInterface $packageFilterService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(self::class);
    }

    /**
     * Create project.
     */
    public function createProject(RequestContext $requestContext, CreateProjectRequestDTO $requestDTO): array
    {
        $this->logger->info('Begin initializing user project');
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Check if topic exists
        $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getWorkspaceId());
        if (empty($workspaceEntity)) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found');
        }

        // If work directory is specified, extract project id from work directory
        $projectId = '';
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($dataIsolation->getCurrentOrganizationCode());
        if (! empty($requestDTO->getWorkDir()) && WorkDirectoryUtil::isValidWorkDirectory($fullPrefix, $requestDTO->getWorkDir())) {
            $projectId = WorkDirectoryUtil::extractProjectIdFromAbsolutePath($requestDTO->getWorkDir());
        }

        Db::beginTransaction();
        try {
            // Create default project
            $this->logger->info('Create default project');
            $projectEntity = $this->projectDomainService->createProject(
                $workspaceEntity->getId(),
                $requestDTO->getProjectName(),
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                $projectId,
                '',
                $requestDTO->getProjectMode() ?: null
            );
            $this->logger->info(sprintf('Create default project, projectId=%s', $projectEntity->getId()));
            // Get project directory
            $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getCurrentUserId(), $projectEntity->getId());

            // Initialize Delightful Chat Conversation
            [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initDelightfulChatConversation($dataIsolation);

            // Create conversation
            // Step 4: Create default topic
            $this->logger->info('Begin creating default topic');
            $topicEntity = $this->topicDomainService->createTopic(
                $dataIsolation,
                $workspaceEntity->getId(),
                $projectEntity->getId(),
                $chatConversationId,
                $chatConversationTopicId,
                '',
                $workDir
            );
            $this->logger->info(sprintf('Create default topic successful, topicId=%s', $topicEntity->getId()));

            // Set workspace information
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

            // If attachments are not empty and attachments are unbound state, save attachments and initialize directories
            if ($requestDTO->getFiles()) {
                $this->taskFileDomainService->bindProjectFiles(
                    $dataIsolation,
                    $projectEntity,
                    $requestDTO->getFiles(),
                    $projectEntity->getWorkDir()
                );
            } else {
                // If no attachments, only initialize project root directory files
                $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $projectEntity->getId(),
                    workDir: $projectEntity->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $projectEntity->getUserOrganizationCode(),
                );
            }

            // Initialize project members and settings
            $this->projectMemberDomainService->initializeProjectMemberAndSettings(
                $dataIsolation->getCurrentUserId(),
                $projectEntity->getId(),
                $workspaceEntity->getId(),
                $dataIsolation->getCurrentOrganizationCode()
            );

            Db::commit();

            // Publish project created event
            $userAuthorization = $requestContext->getUserAuthorization();
            $projectCreatedEvent = new ProjectCreatedEvent($projectEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($projectCreatedEvent);

            return ['project' => ProjectItemDTO::fromEntity($projectEntity)->toArray(), 'topic' => TopicItemDTO::fromEntity($topicEntity)->toArray()];
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error('Create Project Failed, err: ' . $e->getMessage(), ['request' => $requestDTO->toArray()]);
            ExceptionBuilder::throw(BeAgentErrorCode::CREATE_PROJECT_FAILED, 'project.create_project_failed');
        }
    }

    /**
     * Update project.
     */
    public function updateProject(RequestContext $requestContext, UpdateProjectRequestDTO $requestDTO): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get project information
        $projectEntity = $this->projectDomainService->getProject((int) $requestDTO->getId(), $dataIsolation->getCurrentUserId());

        if (! is_null($requestDTO->getProjectName())) {
            $projectEntity->setProjectName($requestDTO->getProjectName());
        }
        if (! is_null($requestDTO->getProjectDescription())) {
            $projectEntity->setProjectDescription($requestDTO->getProjectDescription());
        }
        if (! is_null($requestDTO->getWorkspaceId())) {
            // Check if topic exists
            $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getWorkspaceId());
            if (empty($workspaceEntity)) {
                ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found');
            }
            $projectEntity->setWorkspaceId($requestDTO->getWorkspaceId());
        }
        if (! is_null($requestDTO->getIsCollaborationEnabled())) {
            $projectEntity->setIsCollaborationEnabled($requestDTO->getIsCollaborationEnabled());
        }
        if (! is_null($requestDTO->getDefaultJoinPermission())) {
            $projectEntity->setDefaultJoinPermission(MemberRole::validatePermissionLevel($requestDTO->getDefaultJoinPermission()));
        }

        $this->projectDomainService->saveProjectEntity($projectEntity);

        // Publish project updated event
        $userAuthorization = $requestContext->getUserAuthorization();
        $projectUpdatedEvent = new ProjectUpdatedEvent($projectEntity, $userAuthorization);
        $this->eventDispatcher->dispatch($projectUpdatedEvent);

        return ProjectItemDTO::fromEntity($projectEntity)->toArray();
    }

    /**
     * Delete project.
     */
    #[Transactional]
    public function deleteProject(RequestContext $requestContext, int $projectId): bool
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // First get project entity for event dispatch
        $projectEntity = $this->projectDomainService->getProject($projectId, $dataIsolation->getCurrentUserId());

        $result = Db::transaction(function () use ($projectId, $dataIsolation) {
            // Delete project
            $result = $this->projectDomainService->deleteProject($projectId, $dataIsolation->getCurrentUserId());

            // Delete project collaboration relationships
            $this->projectMemberDomainService->deleteByProjectId($projectId);
            return $result;
        });

        if ($result) {
            // Delete long-term memory related to project
            $this->longTermMemoryDomainService->deleteMemoriesByProjectIds(
                $dataIsolation->getCurrentOrganizationCode(),
                AgentConstant::BE_DELIGHTFUL_CODE, // app_id fixed as be-delightful
                $dataIsolation->getCurrentUserId(),
                [(string) $projectId]
            );

            // Publish project deleted event
            $projectDeletedEvent = new ProjectDeletedEvent($projectEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($projectDeletedEvent);

            $this->topicDomainService->deleteTopicsByProjectId($dataIsolation, $projectId);
            $event = new StopRunningTaskEvent(
                DeleteDataType::PROJECT,
                $projectId,
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                'Project has been deleted'
            );
            $publisher = new StopRunningTaskPublisher($event);
            $this->producer->produce($publisher);

            $this->logger->info(sprintf(
                'Stop task message delivered, project ID: %d, event ID: %s',
                $projectId,
                $event->getEventId()
            ));
        }

        return $result;
    }

    /**
     * Get project details.
     */
    public function getProjectInfo(RequestContext $requestContext, int $projectId): ProjectEntity
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        $project = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // If current organization has no paid plan, disable project collaboration
        if (! $this->packageFilterService->isPaidSubscription($project->getUserOrganizationCode())) {
            $project->setIsCollaborationEnabled(false);
        }

        return $project;
    }

    /**
     * Get project details.
     */
    public function getProject(RequestContext $requestContext, int $projectId): ProjectEntity
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        return $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
    }

    /**
     * Get project details.
     */
    public function getProjectNotUserId(int $projectId): ?ProjectEntity
    {
        return $this->projectDomainService->getProjectNotUserId($projectId);
    }

    public function getProjectForkCount(int $projectId): int
    {
        return $this->projectDomainService->getProjectForkCount($projectId);
    }

    /**
     * Get project list (with pagination).
     */
    public function getProjectList(RequestContext $requestContext, GetProjectListRequestDTO $requestDTO): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        $conditions = [];
        $conditions['user_id'] = $dataIsolation->getCurrentUserId();
        $conditions['user_organization_code'] = $dataIsolation->getCurrentOrganizationCode();

        if ($requestDTO->getWorkspaceId()) {
            $conditions['workspace_id'] = $requestDTO->getWorkspaceId();
        }

        // Add project name fuzzy search condition
        if (! empty($requestDTO->getProjectName())) {
            $conditions['project_name_like'] = $requestDTO->getProjectName();
        }

        $result = $this->projectDomainService->getProjectsByConditions(
            $conditions,
            $requestDTO->getPage(),
            $requestDTO->getPageSize(),
            'updated_at',
            'desc'
        );

        // Extract all project IDs and workspace IDs
        $projectIds = array_unique(array_map(fn ($project) => $project->getId(), $result['list'] ?? []));
        $workspaceIds = array_unique(array_map(fn ($project) => $project->getWorkspaceId(), $result['list'] ?? []));

        // Batch get project status
        $projectStatusMap = $this->topicDomainService->calculateProjectStatusBatch($projectIds, $dataIsolation->getCurrentUserId());

        // Batch get workspace names
        $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds);

        // Batch get project member count, determine if there are collaboration members
        $projectMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIds);
        $projectIdsWithMember = array_keys(array_filter($projectMemberCounts, fn ($count) => $count > 0));

        // Create response DTO and pass project status map and workspace name map
        $listResponseDTO = ProjectListResponseDTO::fromResult($result, $workspaceNameMap, $projectIdsWithMember, $projectStatusMap);

        return $listResponseDTO->toArray();
    }

    /**
     * Get topic list under project.
     */
    public function getProjectTopics(RequestContext $requestContext, int $projectId, int $page = 1, int $pageSize = 10): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Verify project access rights
        $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Get project topic list through topic domain service
        $result = $this->topicDomainService->getProjectTopicsWithPagination(
            $projectId,
            $dataIsolation->getCurrentUserId(),
            $page,
            $pageSize
        );

        // Convert to TopicItemDTO
        $topicDTOs = [];
        foreach ($result['list'] as $topic) {
            $topicDTOs[] = TopicItemDTO::fromEntity($topic)->toArray();
        }

        return [
            'total' => $result['total'],
            'list' => $topicDTOs,
        ];
    }

    public function checkFileListUpdate(RequestContext $requestContext, int $projectId, DataIsolation $dataIsolation): array
    {
        //        $userAuthorization = $requestContext->getUserAuthorization();

        //        $projectEntity = $this->projectDomainService->getProject($projectId, $userAuthorization->getId());

        // Get topic attachment list through domain service
        //        $result = $this->taskDomainService->getTaskAttachmentsByTopicId(
        //            (int) $projectEntity->getCurrentTopicId(),
        //            $dataIsolation,
        //            1,
        //            2000
        //        );
        //
        //        $lastUpdatedAt = $this->taskFileDomainService->getLatestUpdatedByProjectId($projectId);
        //        $topicEntity = $this->topicDomainService->getTopicById($projectEntity->getCurrentTopicId());
        //        $taskEntity = $this->taskDomainService->getTaskBySandboxId($topicEntity->getSandboxId());
        //        # #Check if git version matches database files table
        //        $result = $this->workspaceDomainService->diffFileListAndVersionFile($result, $projectId, $dataIsolation->getCurrentOrganizationCode(), (string) $taskEntity->getId(), $topicEntity->getSandboxId());
        //        if ($result) {
        //            $lastUpdatedAt = date('Y-m-d H:i:s');
        //        }

        $lastUpdatedAt = $this->taskFileDomainService->getLatestUpdatedByProjectId($projectId);

        return [
            'last_updated_at' => $lastUpdatedAt,
        ];
    }

    /**
     * Get project attachment list (logged-in user mode).
     */
    public function getProjectAttachments(RequestContext $requestContext, GetProjectAttachmentsRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Verify project existence and ownership
        $projectEntity = $this->getAccessibleProject((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Create user-based data isolation
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get attachment list (pass workDir for relative path calculation)
        return $this->getProjectAttachmentList($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? '');
    }

    /**
     * Get project attachment list V2 (logged-in user mode, no tree structure, supports time filtering).
     */
    public function getProjectAttachmentsV2(RequestContext $requestContext, GetProjectAttachmentsV2RequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Verify project existence and ownership
        $projectEntity = $this->getAccessibleProject((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Create user-based data isolation
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Get attachment list, no tree structure, no storage_type filtering
        return $this->getProjectAttachmentListV2($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? '');
    }

    /**
     * Get project attachment list from audit page.
     */
    public function getProjectAttachmentsFromAudit(RequestContext $requestContext, GetProjectAttachmentsRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $requestDTO->getProjectId());

        // Create user-based data isolation
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        return $this->getProjectAttachmentList($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? '');
    }

    /**
     * Get project attachment list through access token.
     */
    public function getProjectAttachmentsByAccessToken(GetProjectAttachmentsRequestDTO $requestDto): array
    {
        $token = $requestDto->getToken();

        // Get data from cache
        if (! AccessTokenUtil::validate($token)) {
            ExceptionBuilder::throw(ShareErrorCode::PARAMETER_CHECK_FAILURE, 'share.parameter_check_failure');
        }

        $shareId = AccessTokenUtil::getShareId($token);
        $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found');
        }

        // Since the frontend's current topic sharing also obtains the project list interface, we need to handle the case where the share type is a topic, otherwise directly handle ResourceType::Project
        $projectId = '';
        $workDir = '';
        switch ($shareEntity->getResourceType()) {
            case ResourceType::Topic->value:
                $topicEntity = $this->topicDomainService->getTopicWithDeleted((int) $shareEntity->getResourceId());
                if (empty($topicEntity)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
                }
                $projectId = (string) $topicEntity->getProjectId();
                $workDir = $topicEntity->getWorkDir();
                break;
            case ResourceType::Project->value:
                $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $shareEntity->getResourceId());
                if (empty($projectEntity)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found');
                }
                $projectId = (string) $projectEntity->getId();
                $workDir = $projectEntity->getWorkDir();
                break;
            default:
                ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported');
        }

        $requestDto->setProjectId($projectId);
        $organizationCode = AccessTokenUtil::getOrganizationCode($token);
        // Create DataIsolation
        $dataIsolation = DataIsolation::simpleMake($organizationCode, '');

        // Token mode does not require workDir processing, pass empty string
        return $this->getProjectAttachmentList($dataIsolation, $requestDto, $workDir);
    }

    /**
     * Get project attachment list through access token V2 (no tree structure).
     */
    public function getProjectAttachmentsByAccessTokenV2(GetProjectAttachmentsV2RequestDTO $requestDto): array
    {
        $token = $requestDto->getToken();

        // Get data from cache
        if (! AccessTokenUtil::validate($token)) {
            ExceptionBuilder::throw(ShareErrorCode::PARAMETER_CHECK_FAILURE, 'share.parameter_check_failure');
        }

        $shareId = AccessTokenUtil::getShareId($token);
        $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found');
        }

        // Since the frontend's current topic sharing also obtains the project list interface, we need to handle the case where the share type is a topic, otherwise directly handle ResourceType::Project
        $projectId = '';
        $workDir = '';
        switch ($shareEntity->getResourceType()) {
            case ResourceType::Topic->value:
                $topicEntity = $this->topicDomainService->getTopicWithDeleted((int) $shareEntity->getResourceId());
                if (empty($topicEntity)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
                }
                $projectId = (string) $topicEntity->getProjectId();
                $workDir = $topicEntity->getWorkDir();
                break;
            case ResourceType::Project->value:
                $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $shareEntity->getResourceId());
                if (empty($projectEntity)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found');
                }
                $projectId = (string) $projectEntity->getId();
                $workDir = $projectEntity->getWorkDir();
                break;
            default:
                ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported');
        }

        $requestDto->setProjectId($projectId);
        $organizationCode = AccessTokenUtil::getOrganizationCode($token);
        // Create DataIsolation
        $dataIsolation = DataIsolation::simpleMake($organizationCode, '');

        // Token mode does not require workDir processing, pass empty string, V2 does not return tree structure
        return $this->getProjectAttachmentListV2($dataIsolation, $requestDto, $workDir);
    }

    public function getCloudFiles(RequestContext $requestContext, int $projectId): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $projectEntity = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
        return $this->taskFileDomainService->getProjectFilesFromCloudStorage($dataIsolation->getCurrentOrganizationCode(), $projectEntity->getWorkDir());
    }

    public function getProjectRoleByUserId(int $projectId, string $userId): string
    {
        $projectMemberEntity = $this->projectMemberDomainService->getMemberByProjectAndUser($projectId, $userId);

        return $projectMemberEntity ? $projectMemberEntity->getRoleValue() : '';
    }

    public function hasProjectMember(int $projectId): bool
    {
        $projectIdMapMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts([$projectId]);

        return (bool) ($projectIdMapMemberCounts[$projectId] ?? 0) > 0;
    }

    /**
     * Fork project.
     */
    public function forkProject(RequestContext $requestContext, ForkProjectRequestDTO $requestDTO): array
    {
        $this->logger->info('Starting project fork process');
        // Check resource is allow fork
        $resourceShareEntity = $this->resourceShareDomainService->getShareByResourceId($requestDTO->sourceProjectId);
        if (empty($resourceShareEntity) || $resourceShareEntity->getResourceType() != ResourceType::Project->value) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, trans('share.resource_not_found'));
        }
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Validate target workspace exists
        $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getTargetWorkspaceId());
        if (empty($workspaceEntity)) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, trans('workspace.workspace_not_found'));
        }

        // Validate target workspace belongs to user
        if ($workspaceEntity->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_ACCESS_DENIED, trans('workspace.workspace_access_denied'));
        }

        Db::beginTransaction();
        try {
            // Trigger fork start check event
            AsyncEventUtil::dispatch(new ForkProjectStartEvent(
                $dataIsolation->getCurrentOrganizationCode(),
                $dataIsolation->getCurrentUserId()
            ));
            $this->logger->info(sprintf(
                'Dispatched fork project start event, organization: %s, user: %s',
                $dataIsolation->getCurrentOrganizationCode(),
                $dataIsolation->getCurrentUserId()
            ));

            // Create fork record and project
            /**
             * @var ProjectEntity $forkProjectEntity
             * @var ProjectForkEntity $forkProjectRecordEntity
             */
            [$forkProjectEntity, $forkProjectRecordEntity] = $this->projectDomainService->forkProject(
                $requestDTO->getSourceProjectId(),
                $requestDTO->getTargetWorkspaceId(),
                $requestDTO->getTargetProjectName(),
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode()
            );

            $this->logger->info(sprintf(
                'Created fork record, fork project ID: %d, fork record ID: %d',
                $forkProjectEntity->getId(),
                $forkProjectRecordEntity->getId()
            ));

            $this->logger->info(sprintf('Create default project, projectId=%s', $forkProjectEntity->getId()));
            $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getCurrentUserId(), $forkProjectEntity->getId());

            // Initialize Delightful Chat Conversation
            [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initDelightfulChatConversation($dataIsolation);

            // Step 4: Create default topic
            $this->logger->info('Begin creating default topic');
            $topicEntity = $this->topicDomainService->createTopic(
                $dataIsolation,
                $workspaceEntity->getId(),
                $forkProjectEntity->getId(),
                $chatConversationId,
                $chatConversationTopicId,
                '',
                $workDir
            );
            $this->logger->info(sprintf('Create default topic successful, topicId=%s', $topicEntity->getId()));

            // Set workspace information
            $workspaceEntity->setCurrentTopicId($topicEntity->getId());
            $workspaceEntity->setCurrentProjectId($forkProjectEntity->getId());
            $this->workspaceDomainService->saveWorkspaceEntity($workspaceEntity);
            $this->logger->info(sprintf('Workspace %s has set current topic %s', $workspaceEntity->getId(), $topicEntity->getId()));

            $forkProjectEntity->setCurrentTopicId($topicEntity->getId());
            $forkProjectEntity->setWorkspaceId($workspaceEntity->getId());
            $forkProjectEntity->setWorkDir($workDir);
            $this->projectDomainService->saveProjectEntity($forkProjectEntity);
            $this->logger->info(sprintf('Project %s has set current topic %s', $forkProjectEntity->getId(), $topicEntity->getId()));

            // Publish fork event for file migration
            $event = new ProjectForkEvent(
                $requestDTO->getSourceProjectId(),
                $forkProjectEntity->getId(),
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                $forkProjectRecordEntity->getId()
            );
            $publisher = new ProjectForkPublisher($event);
            $this->producer->produce($publisher);

            $this->logger->info(sprintf(
                'Published fork event, event ID: %s',
                $event->getEventId()
            ));

            Db::commit();

            return ForkProjectResponseDTO::fromEntity($forkProjectRecordEntity)->toArray();
        } catch (EventException $e) {
            Db::rollBack();
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_FORK_ACCESS_DENIED, $e->getMessage());
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error('Fork project failed, error: ' . $e->getMessage(), ['request' => $requestDTO->toArray()]);
            throw $e;
        }
    }

    /**
     * Check fork project status.
     */
    public function checkForkProjectStatus(RequestContext $requestContext, int $projectId): array
    {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Find fork record by fork project ID
        $projectFork = $this->projectDomainService->findByForkProjectId($projectId);
        if (! $projectFork) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND, trans('project.project_not_found'));
        }

        // Check if user has access to this fork
        if ($projectFork->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED, trans('project.project_access_denied'));
        }

        return ForkStatusResponseDTO::fromEntity($projectFork)->toArray();
    }

    /**
     * Migrate project file (called by subscriber).
     */
    public function migrateProjectFile(ProjectForkEvent $event): void
    {
        $this->logger->info(sprintf(
            'Starting file migration for fork record ID: %d',
            $event->getForkRecordId()
        ));

        try {
            // Call file domain service to handle file migration
            $dataIsolation = DataIsolation::simpleMake($event->getOrganizationCode(), $event->getUserId());

            $sourceProjectEntity = $this->projectDomainService->getProjectNotUserId($event->getSourceProjectId());

            $forkProjectEntity = $this->projectDomainService->getProjectNotUserId($event->getForkProjectId());

            $forkProjectRecordEntity = $this->projectDomainService->getForkProjectRecordById($event->getForkRecordId());

            $this->taskFileDomainService->migrateProjectFile($dataIsolation, $sourceProjectEntity, $forkProjectEntity, $forkProjectRecordEntity);

            $this->logger->info(sprintf(
                'File migration batch completed for fork record ID: %d',
                $event->getForkRecordId()
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'File migration failed for fork record ID: %d, error: %s',
                $event->getForkRecordId(),
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Move project to another workspace.
     */
    public function moveProject(RequestContext $requestContext, MoveProjectRequestDTO $requestDTO): array
    {
        $this->logger->info('Starting project move process');

        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();

        // Create data isolation object
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Validate target workspace exists and belongs to user
        $targetWorkspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getTargetWorkspaceId());
        if (empty($targetWorkspaceEntity)) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, trans('workspace.workspace_not_found'));
        }

        if ($targetWorkspaceEntity->getUserId() !== $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_ACCESS_DENIED, trans('workspace.workspace_access_denied'));
        }

        // Validate source project exists and belongs to user (only project owner can move)
        $sourceProjectEntity = $this->projectDomainService->getProject(
            $requestDTO->getSourceProjectId(),
            $dataIsolation->getCurrentUserId()
        );

        // Call domain service to handle the move
        $movedProjectEntity = $this->projectDomainService->moveProject(
            $requestDTO->getSourceProjectId(),
            $requestDTO->getTargetWorkspaceId(),
            $userAuthorization->getId()
        );

        $this->logger->info(sprintf(
            'Project moved successfully, project ID: %d, from workspace: %d to workspace: %d',
            $movedProjectEntity->getId(),
            $sourceProjectEntity->getWorkspaceId(),
            $requestDTO->getTargetWorkspaceId()
        ));

        return [
            'project_id' => (string) $movedProjectEntity->getId(),
        ];
    }

    /**
     * Get core logic of project attachment list.
     */
    public function getProjectAttachmentList(DataIsolation $dataIsolation, GetProjectAttachmentsRequestDTO $requestDTO, string $workDir = ''): array
    {
        // Get project attachment list through task domain service
        $result = $this->taskDomainService->getTaskAttachmentsByProjectId(
            (int) $requestDTO->getProjectId(),
            $dataIsolation,
            $requestDTO->getPage(),
            $requestDTO->getPageSize(),
            $requestDTO->getFileType(),
            StorageType::WORKSPACE->value,
        );

        // Process file URL
        $list = [];
        $fileKeys = [];
        // Traverse attachment list, process using TaskFileItemDTO
        foreach ($result['list'] as $entity) {
            /**
             * @var TaskFileEntity $entity
             */
            // Create DTO
            $dto = new TaskFileItemDTO();
            $dto->fileId = (string) $entity->getFileId();
            $dto->taskId = (string) $entity->getTaskId();
            $dto->fileType = $entity->getFileType();
            $dto->fileName = $entity->getFileName();
            $dto->fileExtension = $entity->getFileExtension();
            $dto->fileKey = $entity->getFileKey();
            $dto->fileSize = $entity->getFileSize();
            $dto->isHidden = $entity->getIsHidden();
            $dto->updatedAt = $entity->getUpdatedAt();
            $dto->topicId = (string) $entity->getTopicId();
            $dto->relativeFilePath = WorkDirectoryUtil::getRelativeFilePath($entity->getFileKey(), $workDir);
            $dto->isDirectory = $entity->getIsDirectory();
            $dto->metadata = FileMetadataUtil::getMetadataObject($entity->getMetadata());
            // Add project_id field
            $dto->projectId = (string) $entity->getProjectId();
            // Set sort field
            $dto->sort = $entity->getSort();
            $dto->fileUrl = '';
            $dto->parentId = (string) $entity->getParentId();
            $dto->source = $entity->getSource();
            // Add file_url field
            $fileKey = $entity->getFileKey();
            // Check if file key is duplicate, if duplicate skip
            // If root directory, also skip
            if (in_array($fileKey, $fileKeys) || empty($entity->getParentId())) {
                continue;
            }
            $fileKeys[] = $fileKey;
            $list[] = $dto->toArray();
        }

        // Build tree structure (logged-in user mode specific feature)
        $tree = FileTreeUtil::assembleFilesTreeByParentId($list);

        if ($result['total'] > 3000) {
            $this->logger->error(sprintf('Project attachment list is too large, project ID: %d, total: %d', $requestDTO->getProjectId(), $result['total']));
        }

        return [
            'total' => $result['total'],
            'list' => $list,
            'tree' => $tree,
        ];
    }

    /**
     * Get core logic of project attachment list V2 (no tree structure, supports database-level update time filtering).
     */
    public function getProjectAttachmentListV2(DataIsolation $dataIsolation, GetProjectAttachmentsV2RequestDTO $requestDTO, string $workDir = ''): array
    {
        // Get attachment list under project through task domain service, use database-level time filtering
        $result = $this->taskDomainService->getTaskAttachmentsByProjectId(
            (int) $requestDTO->getProjectId(),
            $dataIsolation,
            $requestDTO->getPage(),
            $requestDTO->getPageSize(),
            $requestDTO->getFileType(),
            StorageType::WORKSPACE->value,  // V2 fixed to use workspace storage type
            $requestDTO->getUpdatedAfter()  // database-level time filtering
        );

        // Process file URL
        $list = [];
        $fileKeys = [];
        // Traverse attachment list, process using TaskFileItemDTO
        foreach ($result['list'] as $entity) {
            /**
             * @var TaskFileEntity $entity
             */
            // Create DTO
            $dto = new TaskFileItemDTO();
            $dto->fileId = (string) $entity->getFileId();
            $dto->taskId = (string) $entity->getTaskId();
            $dto->fileType = $entity->getFileType();
            $dto->fileName = $entity->getFileName();
            $dto->fileExtension = $entity->getFileExtension();
            $dto->fileKey = $entity->getFileKey();
            $dto->fileSize = $entity->getFileSize();
            $dto->isHidden = $entity->getIsHidden();
            $dto->updatedAt = $entity->getUpdatedAt();
            $dto->topicId = (string) $entity->getTopicId();
            $dto->relativeFilePath = WorkDirectoryUtil::getRelativeFilePath($entity->getFileKey(), $workDir);
            $dto->isDirectory = $entity->getIsDirectory();
            $dto->metadata = FileMetadataUtil::getMetadataObject($entity->getMetadata());
            // Add project_id field
            $dto->projectId = (string) $entity->getProjectId();
            // Set sort field
            $dto->sort = $entity->getSort();
            $dto->fileUrl = '';
            $dto->parentId = (string) $entity->getParentId();
            $dto->source = $entity->getSource();
            // Add file_url field
            $fileKey = $entity->getFileKey();
            // Check if file key is duplicate, if duplicate skip
            // If root directory, also skip
            if (in_array($fileKey, $fileKeys) || empty($entity->getParentId())) {
                continue;
            }
            $fileKeys[] = $fileKey;
            $list[] = $dto->toArray();
        }

        return [
            'total' => $result['total'],
            'list' => $list,
        ];
    }
}
