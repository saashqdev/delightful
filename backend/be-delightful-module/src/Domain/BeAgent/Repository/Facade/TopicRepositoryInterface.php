<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;

interface TopicRepositoryInterface
{
    /**
     * Get topic by ID.
     */
    public function getTopicById(int $id): ?TopicEntity;

    /**
     * Batch get topics.
     * @return TopicEntity[]
     */
    public function getTopicsByIds(array $ids): array;

    public function getTopicWithDeleted(int $id): ?TopicEntity;

    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity;

    /**
     * Get topic list by conditions.
     * Supports filtering, pagination and sorting.
     *
     * @param array $conditions Query conditions, e.g. ['workspace_id' => 1, 'user_id' => 'xxx']
     * @param bool $needPagination Whether pagination is needed
     * @param int $pageSize Page size
     * @param int $page Page number
     * @param string $orderBy Sort field
     * @param string $orderDirection Sort direction, asc or desc
     * @return array{list: TopicEntity[], total: int} Topic list and total count
     */
    public function getTopicsByConditions(
        array $conditions = [],
        bool $needPagination = true,
        int $pageSize = 10,
        int $page = 1,
        string $orderBy = 'id',
        string $orderDirection = 'desc'
    ): array;

    /**
     * Create topic.
     */
    public function createTopic(TopicEntity $topicEntity): TopicEntity;

    /**
     * Update topic.
     */
    public function updateTopic(TopicEntity $topicEntity): bool;

    /**
     * Update topic using updated_at as optimistic lock.
     */
    public function updateTopicWithUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool;

    public function updateTopicByCondition(array $condition, array $data): bool;

    /**
     * Delete topic.
     */
    public function deleteTopic(int $id): bool;

    /**
     * Get workspace information by topic ID collection.
     *
     * @param array $topicIds Topic ID collection
     * @return array Associative array with topic ID as key and workspace info as value
     */
    public function getWorkspaceInfoByTopicIds(array $topicIds): array;

    /**
     * Get topic status statistics data.
     *
     * @param array $conditions Statistics conditions, e.g. ['user_id' => '123', 'organization_code' => 'abc']
     * @return array Array containing counts for each status
     */
    public function getTopicStatusMetrics(array $conditions = []): array;

    public function updateTopicStatus(int $id, $taskId, TaskStatus $status): bool;

    public function updateTopicStatusAndSandboxId(int $id, $taskId, TaskStatus $status, string $sandboxId): bool;

    /**
     * Get topic list with last update time exceeding specified time.
     *
     * @param string $timeThreshold Time threshold, topics with update time earlier than this will be included in results
     * @param int $limit Maximum number of results to return
     * @return array<TopicEntity> Topic entity list
     */
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array;

    /**
     * Get topic list by project ID.
     */
    public function getTopicsByProjectId(int $projectId, string $userId): array;

    public function updateTopicStatusBySandboxIds(array $sandboxIds, string $status);

    /**
     * Count topics under project.
     */
    public function countTopicsByProjectId(int $projectId): int;

    public function getRunningWorkspaceIds(array $workspaceIds, ?string $userId = null): array;

    public function getRunningProjectIds(array $projectIds, ?string $userId = null): array;

    // ======================= Message rollback related methods =======================

    /**
     * Get delightful_message_id by sequence ID.
     */
    public function getDelightfulMessageIdBySeqId(string $seqId): ?string;

    /**
     * Get all related seq_ids by delightful_message_id (all perspectives).
     */
    public function getAllSeqIdsByDelightfulMessageId(string $delightfulMessageId): array;

    /**
     * Get current message and all messages after it in current topic by base seq_ids.
     * @param array $baseSeqIds Base seq_ids
     * @return array All related seq_ids
     */
    public function getAllSeqIdsFromCurrent(array $baseSeqIds): array;

    /**
     * Delete topic_messages data.
     */
    public function deleteTopicMessages(array $seqIds): int;

    /**
     * Delete messages and sequences data by seq_ids.
     */
    public function deleteMessagesAndSequencesBySeqIds(array $seqIds): bool;

    /**
     * Delete subsequent messages in delightful_be_agent_message table for corresponding topic by im_seq_id.
     *
     * Deletion logic:
     * 1. Query delightful_be_agent_message table by im_seq_id to get corresponding primary key id and topic_id
     * 2. Delete all data in current topic where id >= queried primary key id
     *
     * @param int $seqId IM message sequence ID
     * @return int Number of deleted records
     */
    public function deleteBeAgentMessagesFromSeqId(int $seqId): int;

    /**
     * Batch update status field in delightful_chat_sequences table.
     *
     * @param array $seqIds Sequence ID array to update
     * @param DelightfulMessageStatus $status Target status
     * @return bool Whether update succeeded
     */
    public function batchUpdateSeqStatus(array $seqIds, DelightfulMessageStatus $status): bool;

    /**
     * Get all messages in current topic less than specified seq_id by base seq_ids.
     *
     * @param array $baseSeqIds Base seq_ids
     * @return array List of all messages less than specified seq_id
     */
    public function getAllSeqIdsBeforeCurrent(array $baseSeqIds): array;

    /**
     * Get all revoked message seq_ids by topic ID.
     *
     * @param int $topicId Topic ID
     * @param string $userId User ID (for permission verification)
     * @return array Revoked message seq_ids
     */
    public function getRevokedSeqIdsByTopicId(int $topicId, string $userId): array;

    /**
     * Batch get topic names by IDs.
     *
     * @param array $topicIds Topic ID array
     * @return array ['topic_id' => 'topic_name'] key-value pairs
     */
    public function getTopicNamesBatch(array $topicIds): array;
}
