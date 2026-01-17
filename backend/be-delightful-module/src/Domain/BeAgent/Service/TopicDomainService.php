<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\CreationSource;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\Query\TopicQuery;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

class TopicDomainService
{
    private LoggerInterface $logger;

    public function __construct(
        protected TopicRepositoryInterface $topicRepository,
        protected TaskRepositoryInterface $taskRepository,
        protected DelightfulMessageRepositoryInterface $delightfulMessageRepository,
        protected DelightfulChatSeqRepositoryInterface $delightfulSeqRepository,
        protected DelightfulChatTopicRepositoryInterface $delightfulChatTopicRepository,
        protected TaskMessageRepositoryInterface $taskMessageRepository,
        protected CloudFileRepositoryInterface $cloudFileRepository,
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('topic');
    }

    public function getTopicById(int $id): ?TopicEntity
    {
        return $this->topicRepository->getTopicById($id);
    }

    public function getTopicWithDeleted(int $id): ?TopicEntity
    {
        return $this->topicRepository->getTopicWithDeleted($id);
    }

    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity
    {
        return $this->topicRepository->getTopicBySandboxId($sandboxId);
    }

    public function getSandboxIdByTopicId(int $topicId): ?string
    {
        $topic = $this->getTopicById($topicId);
        if (empty($topic)) {
            return null;
        }
        return $topic->getSandboxId();
    }

    public function updateTopicStatus(int $id, int $taskId, TaskStatus $taskStatus): bool
    {
        return $this->topicRepository->updateTopicStatus($id, $taskId, $taskStatus);
    }

    public function updateTopicStatusAndSandboxId(int $id, int $taskId, TaskStatus $taskStatus, string $sandboxId): bool
    {
        return $this->topicRepository->updateTopicStatusAndSandboxId($id, $taskId, $taskStatus, $sandboxId);
    }

    /**
     * Get topic list whose update time exceeds specified time.
     *
     * @param string $timeThreshold Time threshold, if topic update time is earlier than this time, it will be included in the result
     * @param int $limit Maximum number of results returned
     * @return array<TopicEntity> Topic entity list
     */
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array
    {
        return $this->topicRepository->getTopicsExceedingUpdateTime($timeThreshold, $limit);
    }

    /**
     * Get topic entity by ChatTopicId.
     */
    public function getTopicByChatTopicId(DataIsolation $dataIsolation, string $chatTopicId): ?TopicEntity
    {
        $conditions = [
            'user_id' => $dataIsolation->getCurrentUserId(),
            'chat_topic_id' => $chatTopicId,
        ];

        $result = $this->topicRepository->getTopicsByConditions($conditions, false);
        if (empty($result['list'])) {
            return null;
        }

        return $result['list'][0];
    }

    public function getTopicMode(DataIsolation $dataIsolation, int $topicId): string
    {
        $conditions = [
            'id' => $topicId,
            'user_id' => $dataIsolation->getCurrentUserId(),
        ];

        $result = $this->topicRepository->getTopicsByConditions($conditions, false);
        if (empty($result['list'])) {
            return '';
        }

        return $result['list'][0]->getTopicMode() ?? '';
    }

    /**
     * @return array<TopicEntity>
     */
    public function getUserRunningTopics(DataIsolation $dataIsolation): array
    {
        $conditions = [
            'user_id' => $dataIsolation->getCurrentUserId(),
            'current_task_status' => TaskStatus::RUNNING,
        ];
        $result = $this->topicRepository->getTopicsByConditions($conditions, false);
        if (empty($result['list'])) {
            return [];
        }

        return $result['list'];
    }

    /**
     * Get topic entity by ChatTopicId.
     */
    public function getTopicOnlyByChatTopicId(string $chatTopicId): ?TopicEntity
    {
        $conditions = [
            'chat_topic_id' => $chatTopicId,
        ];

        $result = $this->topicRepository->getTopicsByConditions($conditions, false);
        if (empty($result['list'])) {
            return null;
        }

        return $result['list'][0];
    }

    public function updateTopicWhereUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool
    {
        return $this->topicRepository->updateTopicWithUpdatedAt($topicEntity, $updatedAt);
    }

    public function updateTopicStatusBySandboxIds(array $sandboxIds, TaskStatus $taskStatus): bool
    {
        return $this->topicRepository->updateTopicStatusBySandboxIds($sandboxIds, $taskStatus->value);
    }

    public function updateTopic(DataIsolation $dataIsolation, int $id, string $topicName): TopicEntity
    {
        // Check if current topic belongs to user
        $topicEntity = $this->topicRepository->getTopicById($id);
        if (empty($topicEntity)) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }
        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied');
        }
        $topicEntity->setTopicName($topicName);

        $this->topicRepository->updateTopic($topicEntity);

        return $topicEntity;
    }

    /**
     * Create topic.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $workspaceId Workspace ID
     * @param int $projectId Project ID
     * @param string $chatConversationId Chat conversation ID
     * @param string $chatTopicId Chat topic ID
     * @param string $topicName Topic name
     * @param string $workDir Work directory
     * @return TopicEntity Created topic entity
     * @throws Exception If creation fails
     */
    public function createTopic(
        DataIsolation $dataIsolation,
        int $workspaceId,
        int $projectId,
        string $chatConversationId,
        string $chatTopicId,
        string $topicName = '',
        string $workDir = '',
        string $topicMode = '',
        int $source = CreationSource::USER_CREATED->value,
        string $sourceId = ''
    ): TopicEntity {
        // Get current user info
        $userId = $dataIsolation->getCurrentUserId();
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        $currentTime = date('Y-m-d H:i:s');

        // Validate required parameters
        if (empty($chatTopicId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic.id_required');
        }

        // Create topic entity
        $topicEntity = new TopicEntity();
        $topicEntity->setUserId($userId);
        $topicEntity->setUserOrganizationCode($organizationCode);
        $topicEntity->setWorkspaceId($workspaceId);
        $topicEntity->setProjectId($projectId);
        $topicEntity->setChatTopicId($chatTopicId);
        $topicEntity->setChatConversationId($chatConversationId);
        $topicEntity->setTopicName($topicName);
        $topicEntity->setSandboxId(''); // Initially empty
        $topicEntity->setWorkDir($workDir); // Initially empty
        $topicEntity->setCurrentTaskId(0);
        $topicEntity->setCurrentTaskStatus(TaskStatus::WAITING); // Default status: waiting
        $topicEntity->setSource($source);
        $topicEntity->setSourceId($sourceId); // Set source ID
        $topicEntity->setCreatedUid($userId); // Set creator user ID
        $topicEntity->setUpdatedUid($userId); // Set updater user ID
        $topicEntity->setCreatedAt($currentTime);
        if (! empty($topicMode)) {
            $topicEntity->setTopicMode($topicMode);
        }
        return $this->topicRepository->createTopic($topicEntity);
    }

    public function deleteTopicsByWorkspaceId(DataIsolation $dataIsolation, int $workspaceId)
    {
        $conditions = [
            'workspace_id' => $workspaceId,
        ];
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_uid' => $dataIsolation->getCurrentUserId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->topicRepository->updateTopicByCondition($conditions, $data);
    }

    public function deleteTopicsByProjectId(DataIsolation $dataIsolation, int $projectId)
    {
        $conditions = [
            'project_id' => $projectId,
        ];
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_uid' => $dataIsolation->getCurrentUserId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->topicRepository->updateTopicByCondition($conditions, $data);
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
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied');
        }

        // Set deletion time
        $topicEntity->setDeletedAt(date('Y-m-d H:i:s'));
        // Set updater user ID
        $topicEntity->setUpdatedUid($userId);
        $topicEntity->setUpdatedAt(date('Y-m-d H:i:s'));

        // Save update
        return $this->topicRepository->updateTopic($topicEntity);
    }

    /**
     * Get project topics with pagination and sorting.
     */
    public function getProjectTopicsWithPagination(
        int $projectId,
        string $userId,
        int $page = 1,
        int $pageSize = 10
    ): array {
        $conditions = [
            'project_id' => $projectId,
            'user_id' => $userId,
        ];

        return $this->topicRepository->getTopicsByConditions(
            $conditions,
            true, // needPagination
            $pageSize,
            $page,
            'id', // Sort by creation time
            'desc' // Descending order
        );
    }

    /**
     * Batch calculate workspace status.
     *
     * @param array $workspaceIds Workspace ID array
     * @param null|string $userId Optional user ID, when specified only calculate topic status for this user
     * @return array ['workspace_id' => 'status'] key-value pairs
     */
    public function calculateWorkspaceStatusBatch(array $workspaceIds, ?string $userId = null): array
    {
        if (empty($workspaceIds)) {
            return [];
        }

        // Get workspace IDs with running topics from repository layer
        $runningWorkspaceIds = $this->topicRepository->getRunningWorkspaceIds($workspaceIds, $userId);

        // Calculate status for each workspace
        $result = [];
        foreach ($workspaceIds as $workspaceId) {
            $result[$workspaceId] = in_array($workspaceId, $runningWorkspaceIds, true)
                ? TaskStatus::RUNNING->value
                : TaskStatus::WAITING->value;
        }

        return $result;
    }

    /**
     * Batch calculate project status.
     *
     * @param array $projectIds Project ID array
     * @param null|string $userId Optional user ID, when specified only query topics for this user
     * @return array ['project_id' => 'status'] key-value pairs
     */
    public function calculateProjectStatusBatch(array $projectIds, ?string $userId = null): array
    {
        if (empty($projectIds)) {
            return [];
        }

        // Get project IDs with running topics from repository layer
        $runningProjectIds = $this->topicRepository->getRunningProjectIds($projectIds, $userId);

        // Calculate status for each project
        $result = [];
        foreach ($projectIds as $projectId) {
            $result[$projectId] = in_array($projectId, $runningProjectIds, true)
                ? TaskStatus::RUNNING->value
                : TaskStatus::WAITING->value;
        }

        return $result;
    }

    /**
     * Update topic name.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $id Topic primary key ID
     * @param string $topicName Topic name
     * @return bool Whether update succeeded
     * @throws Exception If update fails
     */
    public function updateTopicName(DataIsolation $dataIsolation, int $id, string $topicName): bool
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

        $conditions = [
            'id' => $id,
        ];
        $data = [
            'topic_name' => $topicName,
            'updated_uid' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // Save update
        return $this->topicRepository->updateTopicByCondition($conditions, $data);
    }

    public function updateTopicSandboxId(DataIsolation $dataIsolation, int $id, string $sandboxId): bool
    {
        $conditions = [
            'id' => $id,
        ];
        $data = [
            'sandbox_id' => $sandboxId,
            'updated_uid' => $dataIsolation->getCurrentUserId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->topicRepository->updateTopicByCondition($conditions, $data);
    }

    /**
     * Validate topic for message queue operations.
     * Checks both ownership and running status.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $topicId Topic ID
     * @return TopicEntity Topic entity if validation passes
     * @throws Exception If validation fails
     */
    public function validateTopicForMessageQueue(DataIsolation $dataIsolation, int $topicId): TopicEntity
    {
        // Get topic by ID
        $topicEntity = $this->topicRepository->getTopicById($topicId);
        if (empty($topicEntity)) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        // Check ownership
        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied');
        }

        return $topicEntity;
    }

    /**
     * Check if topic is running by user.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param int $topicId Topic ID
     * @return bool True if topic is running and belongs to user
     */
    public function isTopicRunningByUser(DataIsolation $dataIsolation, int $topicId): bool
    {
        try {
            $this->validateTopicForMessageQueue($dataIsolation, $topicId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // ======================= Message Rollback Related Methods =======================

    /**
     * Execute message rollback logic.
     */
    public function rollbackMessages(string $targetSeqId): void
    {
        // Get delightful_message_id by seq_id
        $delightfulMessageId = $this->topicRepository->getDelightfulMessageIdBySeqId($targetSeqId);
        if (empty($delightfulMessageId)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found');
        }

        // Get all related seq_ids (all perspectives)
        $baseSeqIds = $this->topicRepository->getAllSeqIdsByDelightfulMessageId($delightfulMessageId);
        if (empty($baseSeqIds)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.delightful_message_id_not_found');
        }

        // Get all seq_ids starting from current message (current message and subsequent messages)
        $allSeqIds = $this->topicRepository->getAllSeqIdsFromCurrent($baseSeqIds);
        if (empty($allSeqIds)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found');
        }

        // Execute deletion in transaction
        Db::transaction(function () use ($allSeqIds, $targetSeqId) {
            // Delete topic_messages data
            $this->topicRepository->deleteTopicMessages($allSeqIds);

            // Delete messages and sequences data
            $this->topicRepository->deleteMessagesAndSequencesBySeqIds($allSeqIds);

            // Delete delightful_be_agent_message table data
            $this->topicRepository->deleteBeAgentMessagesFromSeqId((int) $targetSeqId);
        });
    }

    /**
     * Execute message rollback start logic (mark status instead of deletion).
     */
    public function rollbackMessagesStart(string $targetSeqId): void
    {
        // Get delightful_message_id by seq_id
        $delightfulMessageId = $this->topicRepository->getDelightfulMessageIdBySeqId($targetSeqId);
        if (empty($delightfulMessageId)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found');
        }

        // Get all related seq_ids (all perspectives)
        $baseSeqIds = $this->topicRepository->getAllSeqIdsByDelightfulMessageId($delightfulMessageId);
        if (empty($baseSeqIds)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.delightful_message_id_not_found');
        }

        // Get all seq_ids starting from current message (current message and subsequent messages)
        $allSeqIdsFromCurrent = $this->topicRepository->getAllSeqIdsFromCurrent($baseSeqIds);
        if (empty($allSeqIdsFromCurrent)) {
            ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found');
        }

        // Get all messages before current message
        $allSeqIdsBeforeCurrent = $this->topicRepository->getAllSeqIdsBeforeCurrent($baseSeqIds);

        // Execute status update in transaction
        Db::transaction(function () use ($allSeqIdsFromCurrent, $allSeqIdsBeforeCurrent) {
            // 1. Set all messages before target_message_id to read status (normal status)
            if (! empty($allSeqIdsBeforeCurrent)) {
                $this->topicRepository->batchUpdateSeqStatus($allSeqIdsBeforeCurrent, DelightfulMessageStatus::Read);
            }

            // 2. Mark messages greater than or equal to target_message_id as revoked status
            $this->topicRepository->batchUpdateSeqStatus($allSeqIdsFromCurrent, DelightfulMessageStatus::Revoked);
        });
    }

    /**
     * Execute message rollback commit logic (physically delete revoked status messages).
     */
    public function rollbackMessagesCommit(int $topicId, string $userId): void
    {
        // Get all revoked status message seq_ids in this topic
        $revokedSeqIds = $this->topicRepository->getRevokedSeqIdsByTopicId($topicId, $userId);

        if (empty($revokedSeqIds)) {
            // No revoked status messages, return directly
            return;
        }

        // To use existing deletion logic, need to find a target_seq_id for deleteBeAgentMessagesFromSeqId
        // Take minimum seq_id as target (ensure all related be_agent_message are deleted)
        $targetSeqId = min($revokedSeqIds);

        // Execute deletion in transaction (consistent with existing rollbackMessages logic)
        Db::transaction(function () use ($revokedSeqIds, $targetSeqId) {
            // Delete topic_messages data
            $this->topicRepository->deleteTopicMessages($revokedSeqIds);

            // Delete messages and sequences data
            $this->topicRepository->deleteMessagesAndSequencesBySeqIds($revokedSeqIds);

            // Delete delightful_be_agent_message table data
            $this->topicRepository->deleteBeAgentMessagesFromSeqId($targetSeqId);
        });
    }

    /**
     * Execute message rollback undo logic (restore revoked status messages to normal status).
     *
     * @param int $topicId Topic ID
     * @param string $userId User ID (permission verification)
     */
    public function rollbackMessagesUndo(int $topicId, string $userId): void
    {
        $this->logger->info('[TopicDomain] Starting message rollback undo', [
            'topic_id' => $topicId,
            'user_id' => $userId,
        ]);

        // Get all revoked status message seq_ids in this topic
        $revokedSeqIds = $this->topicRepository->getRevokedSeqIdsByTopicId($topicId, $userId);

        if (empty($revokedSeqIds)) {
            $this->logger->info('[TopicDomain] No revoked messages found for undo', [
                'topic_id' => $topicId,
                'user_id' => $userId,
            ]);
            // No revoked status messages, return directly
            return;
        }

        $this->logger->info('[TopicDomain] Found revoked messages for undo', [
            'topic_id' => $topicId,
            'user_id' => $userId,
            'revoked_seq_ids_count' => count($revokedSeqIds),
        ]);

        // Execute status update in transaction (restore revoked status to read status)
        Db::transaction(function () use ($revokedSeqIds) {
            // Restore revoked status messages to read status
            $this->topicRepository->batchUpdateSeqStatus($revokedSeqIds, DelightfulMessageStatus::Read);
        });

        $this->logger->info('[TopicDomain] Message rollback undo completed successfully', [
            'topic_id' => $topicId,
            'user_id' => $userId,
            'restored_seq_ids_count' => count($revokedSeqIds),
        ]);
    }

    /**
     * Get topic list by topic query object.
     *
     * @param TopicQuery $query Topic query object
     * @return array{total: int, list: array<TopicEntity>} Topic list and total count
     */
    public function getTopicsByQuery(TopicQuery $query): array
    {
        $conditions = $query->toConditions();

        // Query topics
        return $this->topicRepository->getTopicsByConditions(
            $conditions,
            true,
            $query->getPageSize(),
            $query->getPage(),
            $query->getOrderBy(),
            $query->getOrder()
        );
    }

    /**
     * Get topic status metrics.
     *
     * @param DataIsolation $dataIsolation Data isolation object
     * @param string $organizationCode Optional organization code filter
     * @return array Topic status metrics data
     */
    public function getTopicStatusMetrics(DataIsolation $dataIsolation, string $organizationCode = ''): array
    {
        // Build query conditions
        $conditions = [];
        // If organization code is provided, add to query conditions
        if (! empty($organizationCode)) {
            $conditions['user_organization_code'] = $organizationCode;
        }

        // Use repository layer to query metrics data
        return $this->topicRepository->getTopicStatusMetrics($conditions);
    }

    /**
     * Batch get topic names by IDs.
     *
     * @param array $topicIds Topic ID array
     * @return array ['topic_id' => 'topic_name'] key-value pairs
     */
    public function getTopicNamesBatch(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        return $this->topicRepository->getTopicNamesBatch($topicIds);
    }

    /**
     * Get chat history download URL for topic.
     *
     * @param int $topicId Topic ID
     * @return string Pre-signed download URL
     * @throws Exception If topic not found
     */
    public function getChatHistoryDownloadUrl(int $topicId): string
    {
        // Get topic entity
        $topicEntity = $this->topicRepository->getTopicById($topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
        }

        // Build file path using WorkDirectoryUtil
        $filePath = WorkDirectoryUtil::getAgentChatHistoryFilePath(
            $topicEntity->getUserId(),
            $topicEntity->getProjectId(),
            $topicId
        );

        // Get organization code from topic entity (not current user)
        $organizationCode = $topicEntity->getUserOrganizationCode();

        // Get full prefix and build complete object key
        $prefix = $this->cloudFileRepository->getFullPrefix($organizationCode);
        $objectKey = rtrim($prefix, '/') . '/' . ltrim($filePath, '/');

        // Generate pre-signed URL for download
        $preSignedUrl = $this->cloudFileRepository->getPreSignedUrlByCredential(
            organizationCode: $organizationCode,
            objectKey: $objectKey,
            bucketType: StorageBucketType::SandBox,
            options: [
                'method' => 'GET',
                'expires' => 3600, // 1 hour expiration
                'filename' => sprintf('chat_history_%d.zip', $topicId), // Set download filename
            ]
        );

        $this->logger->info('Generated chat history download URL', [
            'topic_id' => $topicId,
            'file_path' => $filePath,
            'object_key' => $objectKey,
            'organization_code' => $organizationCode,
        ]);

        return $preSignedUrl;
    }

    /**
     * Duplicate topic skeleton - create topic entity and IM conversation only.
     * This method only creates the topic entity and IM conversation,
     * without copying messages. Use copyTopicMessageFromOthers to copy messages.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param TopicEntity $sourceTopicEntity Source topic entity to duplicate from
     * @param string $newTopicName Name for the new topic
     * @return array Returns array containing topic_entity and im_conversation
     */
    public function duplicateTopicSkeleton(
        DataIsolation $dataIsolation,
        TopicEntity $sourceTopicEntity,
        string $newTopicName
    ): array {
        $this->logger->info('Creating topic skeleton for duplication', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'new_topic_name' => $newTopicName,
        ]);

        // Initialize IM conversation
        $imConversationResult = $this->initImConversationFromTopic($sourceTopicEntity, $newTopicName);

        // Create topic entity
        $targetTopicEntity = $this->copyTopicEntity(
            $dataIsolation,
            $sourceTopicEntity,
            $imConversationResult['user_conversation_id'],
            $imConversationResult['new_topic_id'],
            $newTopicName
        );

        $this->logger->info('Topic skeleton created successfully', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'new_topic_id' => $targetTopicEntity->getId(),
        ]);

        return [
            'topic_entity' => $targetTopicEntity,
            'im_conversation' => $imConversationResult,
        ];
    }

    /**
     * Duplicate topic - complete duplication including skeleton and messages.
     * This is the main method for topic duplication (synchronous).
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param TopicEntity $sourceTopicEntity Source topic entity to duplicate from
     * @param string $newTopicName Name for the new topic
     * @param int $targetMessageId Message ID to copy up to
     * @return TopicEntity The newly created topic entity
     * @throws Throwable
     */
    public function duplicateTopic(
        DataIsolation $dataIsolation,
        TopicEntity $sourceTopicEntity,
        string $newTopicName,
        int $targetMessageId
    ): TopicEntity {
        $this->logger->info('Starting complete topic duplication', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'new_topic_name' => $newTopicName,
            'target_message_id' => $targetMessageId,
        ]);

        // Step 1: Create topic skeleton with IM conversation
        $duplicateResult = $this->duplicateTopicSkeleton(
            $dataIsolation,
            $sourceTopicEntity,
            $newTopicName
        );

        $newTopicEntity = $duplicateResult['topic_entity'];
        $imConversationResult = $duplicateResult['im_conversation'];

        $this->logger->info('Topic skeleton created, starting message copy', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'new_topic_id' => $newTopicEntity->getId(),
        ]);

        // Step 2: Copy messages from source to target
        $this->copyTopicMessageFromOthers(
            $sourceTopicEntity,
            $newTopicEntity,
            $targetMessageId,
            $imConversationResult,
            null // No progress callback needed for synchronous operation
        );

        $this->logger->info('Complete topic duplication finished', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'new_topic_id' => $newTopicEntity->getId(),
        ]);

        return $newTopicEntity;
    }

    /**
     * Copy topic messages from source topic to target topic.
     * This method handles the copying of messages, IM messages, and chat history files.
     *
     * @param TopicEntity $sourceTopicEntity Source topic entity
     * @param TopicEntity $targetTopicEntity Target topic entity
     * @param int $messageId Message ID to copy up to
     * @param array $imConversationResult IM conversation result from duplicateTopicSkeleton
     * @param null|callable $progressCallback Optional progress callback function
     */
    public function copyTopicMessageFromOthers(
        TopicEntity $sourceTopicEntity,
        TopicEntity $targetTopicEntity,
        int $messageId,
        array $imConversationResult,
        ?callable $progressCallback = null
    ): void {
        $this->logger->info('Starting to copy topic messages', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'target_topic_id' => $targetTopicEntity->getId(),
            'message_id' => $messageId,
        ]);

        // Copy messages
        $progressCallback && $progressCallback('running', 20, 'Copying topic messages');
        $messageIdMapping = $this->copyTopicShareMessages($messageId, $sourceTopicEntity, $targetTopicEntity);

        // Get agent's seq id
        $progressCallback && $progressCallback('running', 40, 'Getting sequence IDs');
        $seqList = $this->getSeqIdByMessageId((string) $messageId);
        $userSeqId = (int) $seqList['user_seq_id'];
        $aiSeqId = (int) $seqList['ai_seq_id'];

        // Copy IM messages
        $progressCallback && $progressCallback('running', 60, 'Copying IM messages');
        $this->copyImMessages($imConversationResult, $messageIdMapping, $userSeqId, $aiSeqId, (string) $targetTopicEntity->getId());

        // Copy sandbox chat history
        $progressCallback && $progressCallback('running', 80, 'Copying chat history files');
        $this->copyAiChatHistoryFile($sourceTopicEntity, $targetTopicEntity);

        $progressCallback && $progressCallback('running', 100, 'Topic message copy completed');

        $this->logger->info('Topic messages copied successfully', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'target_topic_id' => $targetTopicEntity->getId(),
        ]);
    }

    private function copyTopicEntity(
        DataIsolation $dataIsolation,
        TopicEntity $sourceTopicEntity,
        string $chatConversationId,
        string $chatTopicId,
        string $newTopicName
    ): TopicEntity {
        $currentTime = date('Y-m-d H:i:s');
        $topicEntity = new TopicEntity();
        $topicEntity->setUserId($sourceTopicEntity->getUserId());
        $topicEntity->setUserOrganizationCode($sourceTopicEntity->getUserOrganizationCode());
        $topicEntity->setWorkspaceId($sourceTopicEntity->getWorkspaceId());
        $topicEntity->setProjectId($sourceTopicEntity->getProjectId());
        $topicEntity->setChatTopicId($chatTopicId);
        $topicEntity->setChatConversationId($chatConversationId);
        $topicEntity->setTopicName($newTopicName);
        $topicEntity->setTopicMode($sourceTopicEntity->getTopicMode());
        $topicEntity->setSandboxId('');
        $topicEntity->setSourceId((string) $sourceTopicEntity->getId()); // Initially empty
        $topicEntity->setSource(CreationSource::COPY->value);
        $topicEntity->setWorkDir($sourceTopicEntity->getWorkDir());
        $topicEntity->setCurrentTaskId(0);
        $topicEntity->setCurrentTaskStatus(TaskStatus::WAITING); // Default status: waiting
        $topicEntity->setCreatedUid($dataIsolation->getCurrentUserId()); // Set creator user ID
        $topicEntity->setUpdatedUid($dataIsolation->getCurrentUserId()); // Set updater user ID
        $topicEntity->setCreatedAt($currentTime);
        $topicEntity->setFromTopicId($sourceTopicEntity->getId());
        return $this->topicRepository->createTopic($topicEntity);
    }

    private function copyTopicShareMessages(int $messageId, TopicEntity $sourceTopicEntity, TopicEntity $targetTopicEntity): array
    {
        $this->logger->info('Starting to copy topic share messages', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'target_topic_id' => $targetTopicEntity->getId(),
            'message_id' => $messageId,
        ]);

        // Query data to copy
        $messagesToCopy = $this->taskMessageRepository->findMessagesToCopyByTopicIdAndMessageId(
            $sourceTopicEntity->getId(),
            $messageId
        );

        if (empty($messagesToCopy)) {
            $this->logger->info('No messages found to copy', [
                'source_topic_id' => $sourceTopicEntity->getId(),
                'message_id' => $messageId,
            ]);
            return [];
        }

        $this->logger->info('Found messages to copy', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'target_topic_id' => $targetTopicEntity->getId(),
            'message_count' => count($messagesToCopy),
        ]);

        // Before processing messages, ensure all messages have associated tasks, so need to supplement tasks
        $taskIds = [];
        foreach ($messagesToCopy as $messageToCopy) {
            if (! in_array($messageToCopy->getTaskId(), $taskIds)) {
                $taskIds[] = $messageToCopy->getTaskId();
            }
        }
        // $taskIdMapping = $this->copyTopicTaskEntity($sourceTopicEntity->getId(), $targetTopicEntity->getId(), $taskIds);

        $newMessageEntities = [];
        $messageIdMapping = []; // Old message ID => New message ID mapping

        foreach ($messagesToCopy as $messageToCopy) {
            $newMessageEntity = new TaskMessageEntity();

            // Copy message properties, update to new topic ID
            $newMessageEntity->setSenderType($messageToCopy->getSenderType());
            $newMessageEntity->setTopicId($targetTopicEntity->getId()); // Set to new topic ID
            $newMessageEntity->setSenderUid($messageToCopy->getSenderUid());
            $newMessageEntity->setReceiverUid($messageToCopy->getReceiverUid());
            $newMessageEntity->setMessageId($messageToCopy->getMessageId());
            $newMessageEntity->setType($messageToCopy->getType());
            $newMessageEntity->setTaskId('');
            $newMessageEntity->setEvent($messageToCopy->getEvent());
            $newMessageEntity->setStatus($messageToCopy->getStatus());
            $newMessageEntity->setSteps($messageToCopy->getSteps());
            $newMessageEntity->setTool($messageToCopy->getTool());
            $newMessageEntity->setAttachments($messageToCopy->getAttachments());
            $newMessageEntity->setMentions($messageToCopy->getMentions());
            $newMessageEntity->setRawData('');
            $newMessageEntity->setContent($messageToCopy->getContent());
            $newMessageEntity->setSeqId($messageToCopy->getSeqId());
            $newMessageEntity->setProcessingStatus($messageToCopy->getProcessingStatus());
            $newMessageEntity->setErrorMessage($messageToCopy->getErrorMessage());
            $newMessageEntity->setRetryCount($messageToCopy->getRetryCount());
            $newMessageEntity->setProcessedAt($messageToCopy->getProcessedAt());
            $newMessageEntity->setShowInUi($messageToCopy->getShowInUi());
            $newMessageEntity->setRawContent($messageToCopy->getRawContent());

            $newMessageEntities[] = $newMessageEntity;

            // Establish mapping: old message ID => new message ID
            $messageIdMapping[$messageToCopy->getId()] = (string) $newMessageEntity->getId();
        }

        // Batch insert to new topic
        $createdMessageEntities = $this->taskMessageRepository->batchCreateMessages($newMessageEntities);

        $this->logger->debug('Successfully copied topic share messages', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'target_topic_id' => $targetTopicEntity->getId(),
            'copied_count' => count($createdMessageEntities),
            'message_id_mapping' => $messageIdMapping,
        ]);

        return $messageIdMapping;
    }

    private function initImConversationFromTopic(TopicEntity $sourceTopicEntity, string $topicName = ''): array
    {
        $this->logger->info('Starting IM conversation initialization from topic', [
            'source_topic_id' => $sourceTopicEntity->getId(),
            'chat_topic_id' => $sourceTopicEntity->getChatTopicId(),
            'chat_conversation_id' => $sourceTopicEntity->getChatConversationId(),
        ]);

        // 1. Query delightful_chat_topics table by chat_topic_id to get all related records
        $existingTopics = $this->delightfulChatTopicRepository->getTopicsByTopicId($sourceTopicEntity->getChatTopicId());

        if (count($existingTopics) !== 2) {
            ExceptionBuilder::throw(
                BeAgentErrorCode::TOPIC_NOT_FOUND,
                trans('be_agent.topic.im_topic_not_found')
            );
        }

        // 2. Generate new topic ID
        $newTopicId = (string) IdGenerator::getSnowId();
        $aiConversationId = '';
        $userConversationId = '';

        // 3. Determine role in loop and create records directly
        foreach ($existingTopics as $topic) {
            $newTopicEntity = new DelightfulTopicEntity();
            $newTopicEntity->setTopicId($newTopicId);
            $newTopicEntity->setConversationId($topic->getConversationId());
            $newTopicEntity->setName(! empty($topicName) ? $topicName : $sourceTopicEntity->getTopicName());
            $newTopicEntity->setDescription($topic->getDescription());
            $newTopicEntity->setOrganizationCode($topic->getOrganizationCode());

            // Save new topic record
            $this->delightfulChatTopicRepository->createTopic($newTopicEntity);

            // Determine AI and user conversation IDs
            if ($topic->getConversationId() === $sourceTopicEntity->getChatConversationId()) {
                $userConversationId = $topic->getConversationId();
            } else {
                $aiConversationId = $topic->getConversationId();
            }
        }

        // Verify conversation IDs are found
        if (empty($aiConversationId) || empty($userConversationId)) {
            ExceptionBuilder::throw(
                BeAgentErrorCode::TOPIC_NOT_FOUND,
                trans('be_agent.topic.conversation_mismatch')
            );
        }

        $result = [
            'ai_conversation_id' => $aiConversationId,
            'user_conversation_id' => $userConversationId,
            'old_topic_id' => $sourceTopicEntity->getChatTopicId(),
            'new_topic_id' => $newTopicId,
        ];

        $this->logger->info('IM conversation initialization completed', $result);

        return $result;
    }

    private function copyImMessages(array $imConversationResult, array $messageIdMapping, int $userSeqId, int $aiSeqId, string $newTopicId): array
    {
        $this->logger->info('Starting IM message copy', [
            'user_seq_id' => $userSeqId,
            'ai_seq_id' => $aiSeqId,
            'im_conversation_result' => $imConversationResult,
            'new_topic_id' => $newTopicId,
        ]);

        // Process delightful_chat_topic_messages table
        // 1. Query user's topic messages
        $userTopicMessages = $this->delightfulChatTopicRepository->getTopicMessagesBySeqId(
            $imConversationResult['user_conversation_id'],
            $imConversationResult['old_topic_id'],
            $userSeqId
        );

        // 2. Query AI's topic messages
        $aiTopicMessages = $this->delightfulChatTopicRepository->getTopicMessagesBySeqId(
            $imConversationResult['ai_conversation_id'],
            $imConversationResult['old_topic_id'],
            $aiSeqId
        );

        $this->logger->info('Queried IM messages', [
            'user_messages_count' => count($userTopicMessages),
            'ai_messages_count' => count($aiTopicMessages),
        ]);

        // 3. Prepare batch insert data
        $batchInsertData = [];
        $userSeqIds = [];
        $aiSeqIds = [];
        $seqIdsMap = [];
        $currentTime = date('Y-m-d H:i:s');

        // Process user messages
        foreach ($userTopicMessages as $userMessage) {
            $newSeqId = (string) IdGenerator::getSnowId();
            $seqIdsMap[$userMessage->getSeqId()] = $newSeqId;
            $userSeqIds[] = $userMessage->getSeqId();
            $batchInsertData[] = [
                'seq_id' => $newSeqId,
                'conversation_id' => $imConversationResult['user_conversation_id'],
                'topic_id' => $imConversationResult['new_topic_id'],
                'organization_code' => $userMessage->getOrganizationCode(),
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ];
        }

        // Process AI messages
        foreach ($aiTopicMessages as $aiMessage) {
            $newSeqId = (string) IdGenerator::getSnowId();
            $seqIdsMap[$aiMessage->getSeqId()] = $newSeqId;
            $aiSeqIds[] = $aiMessage->getSeqId();
            $batchInsertData[] = [
                'seq_id' => $newSeqId,
                'conversation_id' => $imConversationResult['ai_conversation_id'],
                'topic_id' => $imConversationResult['new_topic_id'],
                'organization_code' => $aiMessage->getOrganizationCode(),
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ];
        }

        // 4. Batch insert messages
        $insertResult = false;
        if (! empty($batchInsertData)) {
            $insertResult = $this->delightfulChatTopicRepository->createTopicMessages($batchInsertData);
        }

        // 5. Process delightful_chat_sequences table
        $delightfulMessageIdMapping = [];
        $batchSeqInsertData = [];

        // 5.1 Query user's sequences
        $userSequences = $this->delightfulSeqRepository->getSequencesByConversationIdAndSeqIds(
            $imConversationResult['user_conversation_id'],
            $userSeqIds
        );

        // 5.2 Query AI's sequences
        $aiSequences = $this->delightfulSeqRepository->getSequencesByConversationIdAndSeqIds(
            $imConversationResult['ai_conversation_id'],
            $aiSeqIds
        );

        $this->logger->info('Queried Seq messages', [
            'user_sequences_count' => count($userSequences),
            'ai_sequences_count' => count($aiSequences),
        ]);

        // 5.3 Process user sequences
        foreach ($userSequences as $userSeq) {
            $originalSeqId = $userSeq->getId();
            $newSeqId = $seqIdsMap[$originalSeqId] ?? null;

            if (! $newSeqId) {
                continue;
            }

            // Generate or get delightful_message_id mapping
            $originalDelightfulMessageId = $userSeq->getDelightfulMessageId();
            if (! isset($delightfulMessageIdMapping[$originalDelightfulMessageId])) {
                $delightfulMessageIdMapping[$originalDelightfulMessageId] = IdGenerator::getUniqueId32();
            }
            $newDelightfulMessageId = $delightfulMessageIdMapping[$originalDelightfulMessageId];

            // Process topic_id replacement in extra
            $extra = $userSeq->getExtra();
            if ($extra && $extra->getTopicId()) {
                $extraData = $extra->toArray();
                $extraData['topic_id'] = $imConversationResult['new_topic_id'];
                $newExtra = new SeqExtra($extraData);
            } else {
                $newExtra = $extra;
            }

            // Get sender_message_id
            $senderMessageId = $seqIdsMap[$userSeq->getSenderMessageId()] ?? '';

            // Process app_message_id - use messageIdMapping
            $originalAppMessageId = $userSeq->getAppMessageId();
            $appMessageId = ! empty($messageIdMapping[$originalAppMessageId]) ? $messageIdMapping[$originalAppMessageId] : (string) IdGenerator::getSnowId();

            // Create new sequence entity
            $newUserSeq = new DelightfulSeqEntity();
            $newUserSeq->setId($newSeqId);
            $newUserSeq->setOrganizationCode($userSeq->getOrganizationCode());
            $newUserSeq->setObjectType($userSeq->getObjectType());
            $newUserSeq->setObjectId($userSeq->getObjectId());
            $newUserSeq->setSeqId($newSeqId);
            $newUserSeq->setSeqType($userSeq->getSeqType());
            $newUserSeq->setContent($userSeq->getContent());
            $newUserSeq->setDelightfulMessageId($newDelightfulMessageId);
            $newUserSeq->setMessageId($newSeqId);
            $newUserSeq->setReferMessageId($userSeq->getMessageId());
            $newUserSeq->setSenderMessageId($senderMessageId);
            $newUserSeq->setConversationId($imConversationResult['user_conversation_id']);
            $newUserSeq->setStatus($userSeq->getStatus());
            $newUserSeq->setReceiveList($userSeq->getReceiveList());
            $newUserSeq->setExtra($newExtra);
            $newUserSeq->setAppMessageId($appMessageId);
            $newUserSeq->setCreatedAt($currentTime);
            $newUserSeq->setUpdatedAt($currentTime);

            $batchSeqInsertData[] = $newUserSeq;
        }

        // 5.4 Process AI sequences
        foreach ($aiSequences as $aiSeq) {
            $originalSeqId = $aiSeq->getId();
            $newSeqId = $seqIdsMap[$originalSeqId] ?? null;

            if (! $newSeqId) {
                continue;
            }

            // Generate or get delightful_message_id mapping
            $originalDelightfulMessageId = $aiSeq->getDelightfulMessageId();
            if (! isset($delightfulMessageIdMapping[$originalDelightfulMessageId])) {
                $delightfulMessageIdMapping[$originalDelightfulMessageId] = IdGenerator::getUniqueId32();
            }
            $newDelightfulMessageId = $delightfulMessageIdMapping[$originalDelightfulMessageId];

            // Process topic_id replacement in extra
            $extra = $aiSeq->getExtra();
            if ($extra && $extra->getTopicId()) {
                $extraData = $extra->toArray();
                $extraData['topic_id'] = $imConversationResult['new_topic_id'];
                $newExtra = new SeqExtra($extraData);
            } else {
                $newExtra = $extra;
            }

            // Get sender_message_id
            $senderMessageId = $seqIdsMap[$aiSeq->getSenderMessageId()] ?? '';

            // Process app_message_id - use messageIdMapping
            $originalAppMessageId = $aiSeq->getAppMessageId();
            $appMessageId = $messageIdMapping[$originalAppMessageId] ?? '';

            // Create new sequence entity
            $newAiSeq = new DelightfulSeqEntity();
            $newAiSeq->setId($newSeqId);
            $newAiSeq->setOrganizationCode($aiSeq->getOrganizationCode());
            $newAiSeq->setObjectType($aiSeq->getObjectType());
            $newAiSeq->setObjectId($aiSeq->getObjectId());
            $newAiSeq->setSeqId($newSeqId);
            $newAiSeq->setSeqType($aiSeq->getSeqType());
            $newAiSeq->setContent($aiSeq->getContent());
            $newAiSeq->setDelightfulMessageId($newDelightfulMessageId);
            $newAiSeq->setMessageId($newSeqId);
            $newAiSeq->setReferMessageId('');
            $newAiSeq->setSenderMessageId($senderMessageId);
            $newAiSeq->setConversationId($imConversationResult['ai_conversation_id']);
            $newAiSeq->setStatus($aiSeq->getStatus());
            $newAiSeq->setReceiveList($aiSeq->getReceiveList());
            $newAiSeq->setExtra($newExtra);
            $newAiSeq->setAppMessageId($appMessageId);
            $newAiSeq->setCreatedAt($currentTime);
            $newAiSeq->setUpdatedAt($currentTime);

            $batchSeqInsertData[] = $newAiSeq;
        }

        // 5.5 Batch insert sequences
        $seqInsertResult = [];
        if (! empty($batchSeqInsertData)) {
            $seqInsertResult = $this->delightfulSeqRepository->batchCreateSeq($batchSeqInsertData);
        }

        // 6. Process delightful_chat_messages table
        // 6.1 Convert $delightfulMessageIdMapping keys to array $originalDelightfulMessageIds
        $originalDelightfulMessageIds = array_keys($delightfulMessageIdMapping);

        // 6.2 Query original delightful_chat_messages records
        $originalMessages = $this->delightfulMessageRepository->getMessagesByDelightfulMessageIds($originalDelightfulMessageIds);

        $this->logger->info('Queried original Messages', [
            'original_message_ids_count' => count($originalDelightfulMessageIds),
            'found_messages_count' => count($originalMessages),
        ]);

        // 6.3 Generate new delightful_chat_messages records
        // Use messageIdMapping directly, structure: [old_app_message_id] = new_message_id
        $batchMessageInsertData = [];
        foreach ($originalMessages as $originalMessage) {
            $originalDelightfulMessageId = $originalMessage->getDelightfulMessageId();
            $newDelightfulMessageId = $delightfulMessageIdMapping[$originalDelightfulMessageId] ?? null;

            if (! $newDelightfulMessageId) {
                continue;
            }

            // Get original content array
            $contentArray = $originalMessage->getContent()->toArray();

            // For BeAgentCard type, directly replace fields with fixed structure
            if ($originalMessage->getMessageType() === ChatMessageType::BeAgentCard) {
                $contentArray['message_id'] = $messageIdMapping[$contentArray['message_id']] ?? '';
                $contentArray['topic_id'] = $newTopicId;
                $contentArray['task_id'] = '';
            }

            $batchMessageInsertData[] = [
                'sender_id' => $originalMessage->getSenderId(),
                'sender_type' => $originalMessage->getSenderType(),
                'sender_organization_code' => $originalMessage->getSenderOrganizationCode(),
                'receive_id' => $originalMessage->getReceiveId(),
                'receive_type' => $originalMessage->getReceiveType(),
                'receive_organization_code' => $originalMessage->getReceiveOrganizationCode(),
                'message_type' => $originalMessage->getMessageType(),
                'content' => json_encode($contentArray, JSON_UNESCAPED_UNICODE),
                'language' => $originalMessage->getLanguage(),
                'app_message_id' => $messageIdMapping[$originalMessage->getAppMessageId()] ?? '',
                'delightful_message_id' => $newDelightfulMessageId,
                'send_time' => $originalMessage->getSendTime(),
                'current_version_id' => null,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ];
        }

        // 6.5 Batch insert delightful_chat_messages
        $messageInsertResult = false;
        if (! empty($batchMessageInsertData)) {
            $messageInsertResult = $this->delightfulMessageRepository->batchCreateMessages($batchMessageInsertData);
        }

        $result = [
            'user_messages_count' => count($userTopicMessages),
            'ai_messages_count' => count($aiTopicMessages),
            'total_topic_messages_copied' => count($batchInsertData),
            'topic_messages_insert_success' => $insertResult,
            'user_sequences_count' => count($userSequences),
            'ai_sequences_count' => count($aiSequences),
            'total_sequences_copied' => count($batchSeqInsertData),
            'sequences_insert_success' => ! empty($seqInsertResult),
            'delightful_message_id_mappings' => count($delightfulMessageIdMapping),
            'original_messages_found' => count($originalMessages),
            'total_messages_copied' => count($batchMessageInsertData),
            'messages_insert_success' => $messageInsertResult,
            'app_message_id_mappings' => count($messageIdMapping),
        ];

        $this->logger->info('IM message copy completed', $result);

        return $result;
    }

    private function getSeqIdByMessageId(string $messageId): array
    {
        // First query delightful_chat_messages by app_message_id and message_type to get delightful_message_id
        $delightfulMessageId = $this->delightfulMessageRepository->getDelightfulMessageIdByAppMessageId($messageId);

        // Then query delightful_chat_sequences by delightful_message_id to get seq_id
        $seqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($delightfulMessageId);
        $result = [];
        foreach ($seqList as $seq) {
            if ($seq['object_type'] === UserType::Ai->value) {
                $result['ai_seq_id'] = $seq['seq_id'];
            } elseif ($seq['object_type'] === UserType::Human->value) {
                $result['user_seq_id'] = $seq['seq_id'];
            }
        }
        return $result;
    }

    private function copyAiChatHistoryFile(TopicEntity $sourceTopicEntity, TopicEntity $targetTopicEntity)
    {
        $sourcePath = WorkDirectoryUtil::getAgentChatHistoryFilePath($sourceTopicEntity->getUserId(), $sourceTopicEntity->getProjectId(), $sourceTopicEntity->getId());
        $targetPath = WorkDirectoryUtil::getAgentChatHistoryFilePath($targetTopicEntity->getUserId(), $targetTopicEntity->getProjectId(), $targetTopicEntity->getId());
        $prefix = $this->cloudFileRepository->getFullPrefix($sourceTopicEntity->getUserOrganizationCode());
        try {
            $sourceKey = rtrim($prefix, '/') . '/' . ltrim($sourcePath, '/');
            $destinationKey = rtrim($prefix, '/') . '/' . ltrim($targetPath, '/');
            $this->cloudFileRepository->copyObjectByCredential(
                prefix: '/',
                organizationCode: $sourceTopicEntity->getUserOrganizationCode(),
                sourceKey: $sourceKey,
                destinationKey: $destinationKey,
                bucketType: StorageBucketType::SandBox
            );
        } catch (Throwable $e) {
            $this->logger->error('Failed to copy IM message file', [
                'error_message' => $e->getMessage(),
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
            ]);
        }
    }
}
