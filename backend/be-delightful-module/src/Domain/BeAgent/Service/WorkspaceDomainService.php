<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\WorkspaceCreationParams;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\WorkspaceStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\WorkspaceEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\WorkspaceVersionEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\WorkspaceRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\WorkspaceVersionRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class WorkspaceDomainService
{
    public function __construct(
        protected WorkspaceRepositoryInterface $workspaceRepository,
        protected TopicRepositoryInterface $topicRepository,
        protected TaskFileRepositoryInterface $taskFileRepository,
        protected TaskRepositoryInterface $taskRepository,
        protected TaskDomainService $taskDomainService,
        protected WorkspaceVersionRepositoryInterface $workspaceVersionRepository,
        protected SandboxGatewayInterface $gateway,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Create workspace only (without topic creation).
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param string $chatConversationId Chat conversation ID
     * @param string $workspaceName Workspace name
     * @return WorkspaceEntity Created workspace entity
     */
    public function createWorkspace(DataIsolation $dataIsolation, string $chatConversationId, string $workspaceName): WorkspaceEntity
    {
        // Get current user info from DataIsolation
        $currentUserId = $dataIsolation->getCurrentUserId();
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // Create workspace entity
        $currentTime = date('Y-m-d H:i:s');
        $workspaceEntity = new WorkspaceEntity();
        $workspaceEntity->setUserId($currentUserId);
        $workspaceEntity->setUserOrganizationCode($organizationCode);
        $workspaceEntity->setChatConversationId($chatConversationId);
        $workspaceEntity->setName($workspaceName);
        $workspaceEntity->setArchiveStatus(WorkspaceArchiveStatus::NotArchived);
        $workspaceEntity->setWorkspaceStatus(WorkspaceStatus::Normal);
        $workspaceEntity->setCreatedUid($currentUserId);
        $workspaceEntity->setUpdatedUid($currentUserId);
        $workspaceEntity->setCreatedAt($currentTime);
        $workspaceEntity->setUpdatedAt($currentTime);

        // Save workspace using repository
        return $this->workspaceRepository->createWorkspace($workspaceEntity);
    }

    /**
     * Create workspace. Will initialize a topic by default (DEPRECATED - use createWorkspace + TopicDomainService::createTopic)
     * Follow DDD style, domain service handles business logic.
     * @return array Array containing workspace entity and topic entity ['workspace' => WorkspaceEntity, 'topic' => TopicEntity|null]
     * @deprecated Use createWorkspace() and TopicDomainService::createTopic() separately
     */
    public function createWorkspaceWithTopic(DataIsolation $dataIsolation, WorkspaceCreationParams $creationParams): array
    {
        // Get current user ID from DataIsolation as creator ID
        $currentUserId = $dataIsolation->getCurrentUserId();
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // Create workspace entity
        $currentTime = date('Y-m-d H:i:s');
        $workspaceEntity = new WorkspaceEntity();
        $workspaceEntity->setUserId($currentUserId); // Use current user ID
        $workspaceEntity->setUserOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $workspaceEntity->setChatConversationId($creationParams->getChatConversationId());
        $workspaceEntity->setName($creationParams->getWorkspaceName());
        $workspaceEntity->setArchiveStatus(WorkspaceArchiveStatus::NotArchived); // Default: not archived
        $workspaceEntity->setWorkspaceStatus(WorkspaceStatus::Normal); // Default status: normal
        $workspaceEntity->setCreatedUid($currentUserId); // Get from DataIsolation
        $workspaceEntity->setUpdatedUid($currentUserId); // Creator and updater are the same on creation
        $workspaceEntity->setCreatedAt($currentTime);
        $workspaceEntity->setUpdatedAt($currentTime);

        // Use transaction to ensure workspace and topic are created successfully together
        $topicEntity = null;
        // Call repository layer to save workspace
        $savedWorkspaceEntity = $this->workspaceRepository->createWorkspace($workspaceEntity);

        // Create topic
        if ($savedWorkspaceEntity->getId() && ! empty($creationParams->getChatConversationTopicId())) {
            // Create topic entity
            $topicEntity = new TopicEntity();
            $topicEntity->setUserId($currentUserId);
            $topicEntity->setUserOrganizationCode($organizationCode);
            $topicEntity->setWorkspaceId($savedWorkspaceEntity->getId());
            $topicEntity->setChatTopicId($creationParams->getChatConversationTopicId());
            $topicEntity->setChatConversationId($creationParams->getChatConversationId());
            $topicEntity->setSandboxId(''); // Initially empty
            $topicEntity->setWorkDir(''); // Initially empty
            $topicEntity->setCurrentTaskId(0);
            $topicEntity->setTopicName($creationParams->getTopicName());
            $topicEntity->setCurrentTaskStatus(TaskStatus::WAITING); // Default status: waiting
            $topicEntity->setCreatedUid($currentUserId); // Set creator user ID
            $topicEntity->setUpdatedUid($currentUserId); // Set updater user ID

            // Use topicRepository to save topic
            $savedTopicEntity = $this->topicRepository->createTopic($topicEntity);

            if ($savedTopicEntity->getId()) {
                // Set workspace's current topic ID to newly created topic ID
                $savedWorkspaceEntity->setCurrentTopicId($savedTopicEntity->getId());
                // Update workspace
                $this->workspaceRepository->save($savedWorkspaceEntity);
                // Update work directory
                $topicEntity->setWorkDir($this->generateWorkDir($currentUserId, $savedTopicEntity->getId()));
                $this->topicRepository->updateTopic($topicEntity);
            }

            $topicEntity = $savedTopicEntity;
        }

        $result = $savedWorkspaceEntity;
        return [
            'workspace' => $result,
            'topic' => $topicEntity,
        ];
    }

    /**
     * Update workspace.
     * Follow DDD style, domain service handles business logic.
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $workspaceId Workspace ID
     * @param string $workspaceName Workspace name
     * @return bool Whether update succeeded
     */
    public function updateWorkspace(DataIsolation $dataIsolation, int $workspaceId, string $workspaceName = ''): bool
    {
        // Get workspace entity
        $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId);

        if (! $workspaceEntity) {
            throw new RuntimeException('Workspace not found');
        }

        if ($workspaceEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            throw new RuntimeException('You are not allowed to update this workspace');
        }

        // If workspace name is provided, update name
        if (! empty($workspaceName)) {
            $workspaceEntity->setName($workspaceName);
            $workspaceEntity->setUpdatedAt(date('Y-m-d H:i:s'));
            $workspaceEntity->setUpdatedUid($dataIsolation->getCurrentUserId()); // Set updater user ID
        }

        // Use generic save method to save
        $this->workspaceRepository->save($workspaceEntity);
        return true;
    }

    /**
     * Get workspace details.
     */
    public function getWorkspaceDetail(int $workspaceId): ?WorkspaceEntity
    {
        return $this->workspaceRepository->getWorkspaceById($workspaceId);
    }

    /**
     * Archive/unarchive workspace.
     */
    public function archiveWorkspace(RequestContext $requestContext, int $workspaceId, bool $isArchived): bool
    {
        $archiveStatus = $isArchived ? WorkspaceArchiveStatus::Archived : WorkspaceArchiveStatus::NotArchived;
        return $this->workspaceRepository->updateWorkspaceArchivedStatus($workspaceId, $archiveStatus->value);
    }

    /**
     * Delete workspace (logical deletion).
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $workspaceId Workspace ID
     * @return bool Whether deletion succeeded
     * @throws RuntimeException If workspace does not exist, throws exception
     */
    public function deleteWorkspace(DataIsolation $dataIsolation, int $workspaceId): bool
    {
        // Get workspace entity
        $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId);

        if (! $workspaceEntity) {
            // Use ExceptionBuilder to throw "not found" type error
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found');
        }

        // If not own workspace, cannot delete
        if ($workspaceEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_ACCESS_DENIED, 'workspace.access_denied');
        }

        // Set deletion time
        $workspaceEntity->setDeletedAt(date('Y-m-d H:i:s'));
        $workspaceEntity->setUpdatedUid($dataIsolation->getCurrentUserId());
        $workspaceEntity->setUpdatedAt(date('Y-m-d H:i:s'));

        // Save update
        $this->workspaceRepository->save($workspaceEntity);
        return true;
    }

    /**
     * Set current topic.
     */
    public function setCurrentTopic(RequestContext $requestContext, int $workspaceId, string $topicId): bool
    {
        return $this->workspaceRepository->updateWorkspaceCurrentTopic($workspaceId, $topicId);
    }

    /**
     * Get workspace list by conditions.
     */
    public function getWorkspacesByConditions(
        array $conditions,
        int $page,
        int $pageSize,
        string $orderBy,
        string $orderDirection,
        DataIsolation $dataIsolation
    ): array {
        // Apply data isolation
        $conditions = $this->applyDataIsolation($conditions, $dataIsolation);

        // Call repository layer to get data
        return $this->workspaceRepository->getWorkspacesByConditions(
            $conditions,
            $page,
            $pageSize,
            $orderBy,
            $orderDirection
        );
    }

    /**
     * Get workspace topic list.
     * @param array $workspaceIds Workspace ID array
     * @param DataIsolation $dataIsolation Data isolation object
     * @param bool $needPagination Whether pagination is needed
     * @param int $pageSize Items per page
     * @param int $page Page number
     * @param string $orderBy Sort field
     * @param string $orderDirection Sort direction
     * @return array Topic list
     */
    public function getWorkspaceTopics(
        array $workspaceIds,
        DataIsolation $dataIsolation,
        bool $needPagination = true,
        int $pageSize = 20,
        int $page = 1,
        string $orderBy = 'id',
        string $orderDirection = 'desc'
    ): array {
        $conditions = [
            'workspace_id' => $workspaceIds,
            'user_id' => $dataIsolation->getCurrentUserId(),
        ];

        return $this->topicRepository->getTopicsByConditions(
            $conditions,
            $needPagination,
            $pageSize,
            $page,
            $orderBy,
            $orderDirection
        );
    }

    /**
     * Get task attachment list.
     *
     * @param int $taskId Task ID
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Attachment list and total count
     */
    public function getTaskAttachments(int $taskId, DataIsolation $dataIsolation, int $page = 1, int $pageSize = 20): array
    {
        // Call TaskFileRepository to get file list
        return $this->taskFileRepository->getByTaskId($taskId, $page, $pageSize);
        // Return entity object list directly, let application layer handle URL retrieval
    }

    /**
     * Create topic.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $workspaceId Workspace ID
     * @param string $chatTopicId Conversation topic ID, stored in topic_id field
     * @param string $topicName Topic name
     * @return TopicEntity Created topic entity
     * @throws Exception If creation fails
     */
    public function createTopic(DataIsolation $dataIsolation, int $workspaceId, string $chatTopicId, string $topicName): TopicEntity
    {
        // Get current user ID
        $userId = $dataIsolation->getCurrentUserId();
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // Get workspace details, check if workspace exists
        $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId);
        if (! $workspaceEntity) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.not_found');
        }

        // Check if workspace is archived
        if ($workspaceEntity->getArchiveStatus() === WorkspaceArchiveStatus::Archived) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.archived');
        }

        // Get conversation ID
        $chatConversationId = $workspaceEntity->getChatConversationId();
        if (empty($chatConversationId)) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'workspace.conversation_id_not_found');
        }

        // If topic ID is empty, throw exception
        if (empty($chatTopicId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic.id_required');
        }

        // Create topic entity
        $topicEntity = new TopicEntity();
        $topicEntity->setUserId($userId);
        $topicEntity->setUserOrganizationCode($organizationCode);
        $topicEntity->setWorkspaceId($workspaceId);
        $topicEntity->setChatTopicId($chatTopicId);
        $topicEntity->setChatConversationId($chatConversationId);
        $topicEntity->setTopicName($topicName);
        $topicEntity->setSandboxId(''); // Initially empty
        $topicEntity->setWorkDir(''); // Initially empty
        $topicEntity->setCurrentTaskId(0);
        $topicEntity->setCurrentTaskStatus(TaskStatus::WAITING); // Default status: waiting
        $topicEntity->setCreatedUid($userId); // Set creator user ID
        $topicEntity->setUpdatedUid($userId); // Set updater user ID

        // Save topic
        $topicEntity = $this->topicRepository->createTopic($topicEntity);
        // Update workspace
        if ($topicEntity->getId()) {
            $topicEntity->setWorkDir($this->generateWorkDir($userId, $topicEntity->getId()));
            $this->topicRepository->updateTopic($topicEntity);
        }
        return $topicEntity;
    }

    /**
     * Get topic entity by ID.
     *
     * @param int $id Topic ID (primary key)
     * @return null|TopicEntity Topic entity
     */
    public function getTopicById(int $id): ?TopicEntity
    {
        return $this->topicRepository->getTopicById($id);
    }

    /**
     * Batch get topics.
     * @return TopicEntity[]
     */
    public function getTopicsByIds(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }
        return $this->topicRepository->getTopicsByIds($topicIds);
    }

    /**
     * Update topic project association.
     *
     * @param int $topicId Topic ID
     * @param int $projectId Project ID
     * @return bool Whether the update was successful
     * @throws Exception If the update fails
     */
    public function updateTopicProject(int $topicId, int $projectId): bool
    {
        // Get topic entity by ID
        $topicEntity = $this->topicRepository->getTopicById($topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found');
        }

        // Update project association
        $topicEntity->setProjectId($projectId);

        // Save update
        return $this->topicRepository->updateTopic($topicEntity);
    }

    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity
    {
        $topics = $this->topicRepository->getTopicsByConditions(['sandbox_id' => $sandboxId], true, 1, 1);
        if (! isset($topics['list']) || empty($topics['list'])) {
            return null;
        }
        return $topics['list'][0];
    }

    /**
     * Save workspace entity
     * Save workspace entity directly without redundant query.
     * @param WorkspaceEntity $workspaceEntity Workspace entity
     * @return WorkspaceEntity Saved workspace entity
     */
    public function saveWorkspaceEntity(WorkspaceEntity $workspaceEntity): WorkspaceEntity
    {
        return $this->workspaceRepository->save($workspaceEntity);
    }

    /**
     * Get workspace topic list.
     *
     * @param array|int $workspaceIds Workspace ID or ID array
     * @param string $userId User ID
     * @return array Topic list with workspace ID as key
     */
    public function getWorkspaceTopicsByWorkspaceIds(array|int $workspaceIds, string $userId): array
    {
        if (! is_array($workspaceIds)) {
            $workspaceIds = [$workspaceIds];
        }

        // If no workspace IDs, return empty array directly
        if (empty($workspaceIds)) {
            return [];
        }

        // Define query conditions
        $conditions = [
            'workspace_id' => $workspaceIds,
            'user_id' => $userId,
        ];

        // Get all matching topics
        $result = $this->topicRepository->getTopicsByConditions(
            $conditions,
            false, // No pagination, get all
            100,
            1,
            'id',
            'asc'
        );

        // Regroup by workspace ID
        $topics = [];
        foreach ($result['list'] as $topic) {
            $workspaceId = $topic->getWorkspaceId();
            if (! isset($topics[$workspaceId])) {
                $topics[$workspaceId] = [];
            }
            $topics[$workspaceId][] = $topic;
        }

        return $topics;
    }

    public function getUserTopics(string $userId): array
    {
        // Consider whether organization code is needed
        $topics = $this->topicRepository->getTopicsByConditions(
            ['user_id' => $userId],
            false, // No pagination, get all
            100,
            1,
            'id',
            'asc'
        );
        if (empty($topics['list'])) {
            return [];
        }

        return $topics['list'];
    }

    public function getTopicList(int $page, int $pageSize): array
    {
        // Consider whether organization code is needed
        // No pagination, get all
        $topics = $this->topicRepository->getTopicsByConditions([], true, $pageSize, $page);
        if (empty($topics['list'])) {
            return [];
        }

        return $topics['list'];
    }

    /**
     * Get workspace topic list by task status.
     *
     * @param array|int $workspaceIds Workspace ID or ID array
     * @param string $userId User ID
     * @param null|TaskStatus $taskStatus Task status, if null returns all statuses
     * @return array Topic list with workspace ID as key
     */
    public function getWorkspaceTopicsByTaskStatus(array|int $workspaceIds, string $userId, ?TaskStatus $taskStatus = null): array
    {
        // Get all topics
        $allTopics = $this->getWorkspaceTopicsByWorkspaceIds($workspaceIds, $userId);

        // If no task status filtering needed, return all topics directly
        if ($taskStatus === null) {
            return $allTopics;
        }

        // Filter topics by task status
        $filteredTopics = [];
        foreach ($allTopics as $workspaceId => $topics) {
            $filteredTopicList = [];
            foreach ($topics as $topic) {
                // If topic's current task status matches specified status, or topic has no task status and specified status is waiting
                if (($topic->getCurrentTaskStatus() === $taskStatus)
                    || ($topic->getCurrentTaskStatus() === null && $taskStatus === TaskStatus::WAITING)) {
                    $filteredTopicList[] = $topic;
                }
            }

            if (! empty($filteredTopicList)) {
                $filteredTopics[$workspaceId] = $filteredTopicList;
            }
        }

        return $filteredTopics;
    }

    /**
     * Delete topic (logical deletion).
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $id Topic ID (primary key)
     * @return bool Whether deletion succeeded
     * @throws Exception If deletion fails or task status is running
     */
    public function deleteTopic(DataIsolation $dataIsolation, int $id): bool
    {
        // Get current user ID
        $userId = $dataIsolation->getCurrentUserId();

        // Get topic by primary key ID
        $topicEntity = $this->topicRepository->getTopicById($id);
        if (! $topicEntity) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found');
        }

        // Check user permission (check if topic belongs to current user)
        if ($topicEntity->getUserId() !== $userId) {
            ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'topic.access_denied');
        }

        // Check task status, if running then deletion not allowed
        if ($topicEntity->getCurrentTaskStatus() === TaskStatus::RUNNING) {
            // Send stop command to agent
            $taskEntity = $this->taskRepository->getTaskById($topicEntity->getCurrentTaskId());
            if (! empty($taskEntity)) {
                $this->taskDomainService->handleInterruptInstruction($dataIsolation, $taskEntity);
            }
        }

        // Get workspace details, check if workspace exists
        $workspaceEntity = $this->workspaceRepository->getWorkspaceById($topicEntity->getWorkspaceId());
        if (! $workspaceEntity) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.not_found');
        }

        // Check if workspace is archived
        if ($workspaceEntity->getArchiveStatus() === WorkspaceArchiveStatus::Archived) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.archived');
        }

        // Delete all tasks under this topic (call repository layer batch delete method)
        $this->taskRepository->deleteTasksByTopicId($id);

        // Set deletion time
        $topicEntity->setDeletedAt(date('Y-m-d H:i:s'));
        // Set updater user ID
        $topicEntity->setUpdatedUid($userId);

        // Save update
        return $this->topicRepository->updateTopic($topicEntity);
    }

    /**
     * Get task details.
     *
     * @param int $taskId Task ID
     * @return null|TaskEntity Task entity
     */
    public function getTaskById(int $taskId): ?TaskEntity
    {
        return $this->taskRepository->getTaskById($taskId);
    }

    /**
     * Get topic associated task list.
     *
     * @param int $topicId Topic ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param null|DataIsolation $dataIsolation Data isolation object
     * @return array{list: TaskEntity[], total: int} Task list and total count
     */
    public function getTasksByTopicId(int $topicId, int $page = 1, int $pageSize = 10, ?DataIsolation $dataIsolation = null): array
    {
        return $this->taskRepository->getTasksByTopicId($topicId, $page, $pageSize);
    }

    /**
     * Get workspace info by topic ID collection.
     *
     * @param array $topicIds Topic ID collection
     * @return array Associative array with topic ID as key and workspace info as value
     */
    public function getWorkspaceInfoByTopicIds(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        return $this->topicRepository->getWorkspaceInfoByTopicIds($topicIds);
    }

    public function updateTopicSandboxConfig(DataIsolation $dataIsolation, int $topicId, array $sandboxConfig): bool
    {
        $topicEntity = $this->topicRepository->getTopicById($topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found');
        }

        $topicEntity->setSandboxConfig(json_encode($sandboxConfig));
        return $this->topicRepository->updateTopic($topicEntity);
    }

    /**
     * Get unique organization code list for all workspaces.
     *
     * @return array Unique organization code list
     */
    public function getUniqueOrganizationCodes(): array
    {
        return $this->workspaceRepository->getUniqueOrganizationCodes();
    }

    /**
     * Create a new workspace version record.
     */
    public function createWorkspaceVersion(WorkspaceVersionEntity $versionEntity): void
    {
        $this->workspaceVersionRepository->create($versionEntity);
    }

    /**
     * Get workspace version by commit hash, topic ID and folder.
     *
     * @param string $commitHash The commit hash
     * @param int $projectId The project ID
     * @param string $folder The folder path
     * @return null|WorkspaceVersionEntity The workspace version entity or null if not found
     */
    public function getWorkspaceVersionByCommitAndProjectId(string $commitHash, int $projectId, string $folder = ''): ?WorkspaceVersionEntity
    {
        // Get all versions for the topic
        return $this->workspaceVersionRepository->findByCommitHashAndProjectId($commitHash, $projectId, $folder);
    }

    /**
     * Get workspace version by commit hash, topic ID and folder.
     *
     * @param int $projectId The project ID
     * @param string $folder The folder path
     * @return null|WorkspaceVersionEntity The workspace version entity or null if not found
     */
    public function getWorkspaceVersionByProjectId(int $projectId, string $folder = ''): ?WorkspaceVersionEntity
    {
        // Get all versions for the topic
        return $this->workspaceVersionRepository->findByProjectId($projectId, $folder);
    }

    public function getLatestVersionByProjectId(int $projectId): ?WorkspaceVersionEntity
    {
        return $this->workspaceVersionRepository->getLatestVersionByProjectId($projectId);
    }

    /**
     * Get tag number by commit_hash and project_id.
     */
    public function getTagByCommitHashAndProjectId(string $commitHash, int $projectId): int
    {
        return $this->workspaceVersionRepository->getTagByCommitHashAndProjectId($commitHash, $projectId);
    }

    /**
     * Batch get workspace name mapping.
     *
     * @param array $workspaceIds Workspace ID array
     * @return array ['workspace_id' => 'workspace_name'] key-value pairs
     */
    public function getWorkspaceNamesBatch(array $workspaceIds): array
    {
        if (empty($workspaceIds)) {
            return [];
        }

        return $this->workspaceRepository->getWorkspaceNamesBatch($workspaceIds);
    }

    /**
     * Get version by commit hash and topic id, then filter result based on dir file list.
     */
    public function filterResultByGitVersion(array $result, int $projectId, string $organizationCode, string $workDir = ''): array
    {
        $dir = '.workspace';
        $workspaceVersion = $this->getWorkspaceVersionByProjectId($projectId, $dir);
        if (empty($workspaceVersion)) {
            return $result;
        }

        if (empty($workspaceVersion->getDir())) {
            return $result;
        }

        # Iterate through result's updatedAt, keep items in temporary array if updatedAt is less than workspaceVersion's updated_at
        $fileResult = [];
        foreach ($result['list'] as $item) {
            if ($item['updated_at'] >= $workspaceVersion->getUpdatedAt()) {
                $fileResult[] = $item;
            }
        }
        $dir = json_decode($workspaceVersion->getDir(), true);
        # dir is a 2D array, iterate through $dir to check if it's a file, filter out directories if no file extension
        # dir =["generated_images","generated_images\/cute-cartoon-cat.jpg","generated_images\/handdrawn-cute-cat.jpg","generated_images\/abstract-modern-generic.jpg","generated_images\/minimalist-cat-icon.jpg","generated_images\/realistic-elegant-cat.jpg","generated_images\/oilpainting-elegant-cat.jpg","generated_images\/anime-cute-cat.jpg","generated_images\/cute-cartoon-dog.jpg","generated_images\/universal-minimal-logo-3.jpg","generated_images\/universal-minimal-logo.jpg","generated_images\/universal-minimal-logo-2.jpg","generated_images\/realistic-cat-photo.jpg","generated_images\/minimal-tech-logo.jpg","logs","logs\/agentlang.log"]
        $dir = array_filter($dir, function ($item) {
            if (strpos($item, '.') === false) {
                return false;
            }
            return true;
        });

        $gitVersionResult = [];
        foreach ($result['list'] as $item) {
            foreach ($dir as $dirItem) {
                $fileKey = WorkDirectoryUtil::getRelativeFilePath($item['file_key'], $workDir);

                // Unify path separators, normalize all path separators to system default separator
                $fileKey = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileKey);
                $dirItem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirItem);
                $dirItem = '/' . $dirItem;
                // Adjust to exact match
                if ($dirItem == $fileKey) {
                    $gitVersionResult[] = $item;
                }
            }
        }

        $newResult = array_merge($fileResult, $gitVersionResult);

        # Deduplicate tempResult
        $result['list'] = array_unique($newResult, SORT_REGULAR);
        $result['total'] = count($result['list']);
        return $result;
    }

    public function diffFileListAndVersionFile(array $result, int $projectId, string $taskId, string $sandboxId, string $organizationCode = ''): bool
    {
        $dir = '.workspace';
        $workspaceVersion = $this->getWorkspaceVersionByProjectId($projectId, $dir);
        if (empty($workspaceVersion)) {
            return false;
        }
        if (empty($workspaceVersion->getDir())) {
            return false;
        }
        $dir = json_decode($workspaceVersion->getDir(), true);
        # dir is a 2D array, iterate through $dir to check if it's a file, filter out directories if no file extension
        # dir =["generated_images","generated_images\/cute-cartoon-cat.jpg","generated_images\/handdrawn-cute-cat.jpg","generated_images\/abstract-modern-generic.jpg","generated_images\/minimalist-cat-icon.jpg","generated_images\/realistic-elegant-cat.jpg","generated_images\/oilpainting-elegant-cat.jpg","generated_images\/anime-cute-cat.jpg","generated_images\/cute-cartoon-dog.jpg","generated_images\/universal-minimal-logo-3.jpg","generated_images\/universal-minimal-logo.jpg","generated_images\/universal-minimal-logo-2.jpg","generated_images\/realistic-cat-photo.jpg","generated_images\/minimal-tech-logo.jpg","logs","logs\/agentlang.log"]

        $dir = array_filter($dir, function ($item) {
            if (strpos($item, '.') === false) {
                return false;
            }
            return true;
        });

        # Iterate through $result, if $result's file_key is in $dir, dir saves part of file_key, need to use string matching, keep in temporary array if exists
        $gitVersionNotExistResult = [];

        $fileKeys = [];
        foreach ($result['list'] as $item) {
            # Find the project_id pattern in the file_key and extract everything after it
            $projectPattern = 'project_' . $projectId;
            $pos = strpos($item['file_key'], $projectPattern);
            if ($pos !== false) {
                # Get the position after the project_id and the following slash
                $startPos = $pos + strlen($projectPattern) + 1; // +1 for the slash
                $fileKeys[] = substr($item['file_key'], $startPos);
            } else {
                # Fallback: if project_id pattern not found, keep original logic
                $fileKeys[] = substr($item['file_key'], strlen((string) $projectId) + 1);
            }
        }

        foreach ($dir as $dirItem) {
            if (! in_array($dirItem, $fileKeys)) {
                $gitVersionNotExistResult[] = $dirItem;
            }
        }

        if (empty($gitVersionNotExistResult)) {
            return false;
        }
        # Deduplicate gitVersionNotExistResult
        $gitVersionNotExistResult = array_unique($gitVersionNotExistResult);

        # Re-sort
        $gitVersionNotExistResult = array_values($gitVersionNotExistResult);

        # If gitVersionNotExistResult is not empty, files are updated but didn't trigger suer-delightful file upload, need to call suer-delightful api for another file upload
        if (! empty($gitVersionNotExistResult)) {
            try {
                # Check if sandbox is alive
                $sandboxStatus = $this->gateway->getSandboxStatus($sandboxId);
                if ($sandboxStatus->isRunning()) {
                    $gatewayResult = $this->gateway->uploadFile($sandboxId, $gitVersionNotExistResult, (string) $projectId, $organizationCode, $taskId);
                    if ($gatewayResult->isSuccess()) {
                        return true;
                    }
                } else {
                    return false;
                }
            } catch (Throwable $e) {
                $this->logger->error('[Sandbox][Domain] uploadFile failed', ['error' => $e->getMessage()]);
            }
        }
        return false;
    }

    /**
     * Apply data isolation to query conditions.
     */
    private function applyDataIsolation(array $conditions, DataIsolation $dataIsolation): array
    {
        // User ID and organization code
        $conditions['user_id'] = $dataIsolation->getCurrentUserId();
        $conditions['user_organization_code'] = $dataIsolation->getCurrentOrganizationCode();
        return $conditions;
    }

    /**
     * Generate work directory.
     */
    private function generateWorkDir(string $userId, int $topicId): string
    {
        return sprintf('/%s/%s/topic_%d', AgentConstant::BE_DELIGHTFUL_CODE, $userId, $topicId);
    }
}
