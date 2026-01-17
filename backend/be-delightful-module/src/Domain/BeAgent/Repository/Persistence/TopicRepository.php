<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatSequenceModel;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatTopicMessageModel;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulMessageModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TopicModel;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\WorkspaceModel;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class TopicRepository implements TopicRepositoryInterface
{
    private LoggerInterface $logger;

    public function __construct(
        protected TopicModel $model,
        protected DelightfulChatSequenceModel $delightfulChatSequenceModel,
        protected DelightfulChatTopicMessageModel $delightfulChatTopicMessageModel,
        protected DelightfulMessageModel $delightfulMessageModel,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(static::class);
    }

    public function getTopicById(int $id): ?TopicEntity
    {
        // Query by id first
        $model = $this->model::query()->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        if ($model) {
            $data = $this->convertModelToEntityData($model->toArray());
            return new TopicEntity($data);
        }

        // If not found by id, query by chat_topic_id
        $model = $this->model::query()->whereNull('deleted_at')
            ->where('chat_topic_id', $id)
            ->first();

        if ($model) {
            // When data is found by chat_topic_id, log error and trace
            $this->logger->error('TopicRepository getTopicById found data by chat_topic_id, possible data inconsistency', [
                'search_id' => $id,
                'found_topic_id' => $model->id,
                'found_chat_topic_id' => $model->chat_topic_id,
                'trace' => (new Exception())->getTraceAsString(),
            ]);

            $data = $this->convertModelToEntityData($model->toArray());
            return new TopicEntity($data);
        }

        return null;
    }

    public function getTopicsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $models = $this->model::query()->whereNull('deleted_at')->whereIn('id', $ids)->get();

        $entities = [];
        foreach ($models as $model) {
            $data = $this->convertModelToEntityData($model->toArray());
            $entities[] = new TopicEntity($data);
        }

        return $entities;
    }

    public function getTopicWithDeleted(int $id): ?TopicEntity
    {
        $model = $this->model::query()->withTrashed()->find($id);
        if (! $model) {
            return null;
        }

        $data = $this->convertModelToEntityData($model->toArray());
        return new TopicEntity($data);
    }

    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity
    {
        $model = $this->model::query()->whereNull('deleted_at')->where('sandbox_id', $sandboxId)->first();
        if (! $model) {
            return null;
        }

        $data = $this->convertModelToEntityData($model->toArray());
        return new TopicEntity($data);
    }

    /**
     * Get topic list by conditions.
     * Supports filtering, pagination, and sorting.
     *
     * @param array $conditions Query conditions, e.g., ['workspace_id' => 1, 'user_id' => 'xxx']
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
    ): array {
        // Build base query
        $query = $this->model::query();

        // Filter deleted data by default
        $query->whereNull('deleted_at');

        // Apply condition filters
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif ($field === 'topic_name') {
                // Use like operation for fuzzy matching on topic_name field
                $query->where($field, 'like', '%' . $value . '%');
            } else {
                $query->where($field, $value);
            }
        }

        // Get total count
        $total = $query->count();

        // Apply sorting
        $query->orderBy($orderBy, $orderDirection);

        // Apply pagination
        if ($needPagination) {
            $offset = ($page - 1) * $pageSize;
            $query->skip($offset)->take($pageSize);
        }

        // Get data
        $topics = Db::select($query->toSql(), $query->getBindings());

        // Convert to entity objects
        $list = [];
        foreach ($topics as $topic) {
            $list[] = new TopicEntity($topic);
        }

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function createTopic(TopicEntity $topicEntity): TopicEntity
    {
        $date = date('Y-m-d H:i:s');
        $topicEntity->setId(IdGenerator::getSnowId());
        $topicEntity->setCreatedAt($date);
        $topicEntity->setUpdatedAt($date);

        $entityArray = $topicEntity->toArray();

        $model = $this->model::query()->create($entityArray);
        /* @var TopicModel $model */
        $topicEntity->setId($model->id);

        return $topicEntity;
    }

    public function updateTopic(TopicEntity $topicEntity): bool
    {
        $topicEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $entityArray = $topicEntity->toArray();

        return $this->model::query()
            ->where('id', $topicEntity->getId())
            ->update($entityArray) > 0;
    }

    // Use updated_at as optimistic lock
    public function updateTopicWithUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool
    {
        $topicEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $entityArray = $topicEntity->toArray();
        return $this->model::query()
            ->where('id', $topicEntity->getId())
            ->where('updated_at', $updatedAt)
            ->update($entityArray) > 0;
    }

    public function updateTopicByCondition(array $condition, array $data): bool
    {
        return $this->model::query()
            ->where($condition)
            ->update($data) > 0;
    }

    public function deleteTopic(int $id): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get workspace information by topic ID collection.
     *
     * @param array $topicIds Topic ID collection
     * @return array Associative array with topic ID as key and workspace info as value, format: ['topic_id' => ['workspace_id' => 'workspace_id', 'workspace_name' => 'workspace_name']]
     */
    public function getWorkspaceInfoByTopicIds(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        // Convert all IDs to integers
        $topicIds = array_map('intval', $topicIds);

        // Use raw SQL join query to improve performance
        $sql = 'SELECT t.id as topic_id, w.id as workspace_id, w.name as workspace_name
                FROM ' . $this->model->getTable() . ' t
                JOIN ' . (new WorkspaceModel())->getTable() . ' w ON t.workspace_id = w.id
                WHERE t.id IN (' . implode(',', $topicIds) . ')
                AND t.deleted_at IS NULL
                AND w.deleted_at IS NULL';

        $results = Db::select($sql);

        // Organize results as associative array with topic ID as key
        $workspaceInfo = [];
        foreach ($results as $row) {
            $workspaceInfo[$row['topic_id']] = [
                'workspace_id' => (string) $row['workspace_id'],
                'workspace_name' => $row['workspace_name'],
            ];
        }

        return $workspaceInfo;
    }

    /**
     * Get topic status statistics.
     *
     * @param array $conditions Statistics conditions, e.g., ['user_id' => '123', 'organization_code' => 'abc']
     * @return array Array containing counts for each status
     */
    public function getTopicStatusMetrics(array $conditions = []): array
    {
        // Use raw SQL query to improve performance, get counts grouped by status
        $baseQuery = $this->model::query();

        // Process filter conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $baseQuery->whereIn($field, $value);
            } else {
                $baseQuery->where($field, $value);
            }
        }

        // Filter deleted data by default
        $baseQuery->whereNull('deleted_at');

        // Count unique users
        $userCount = $baseQuery->distinct()->count('user_id');

        // Count total topics
        $topicCount = $baseQuery->count();

        // Count topics by status
        $statusCounts = $baseQuery
            ->selectRaw('current_task_status, COUNT(*) as count')
            ->groupBy('current_task_status')
            ->get()
            ->keyBy('current_task_status')
            ->map(function ($item) {
                return (int) $item->count;
            })
            ->toArray();

        // Prepare return result
        return [
            'status_metrics' => [
                'error_count' => $statusCounts['error'] ?? 0,
                'completed_count' => $statusCounts['finished'] ?? 0,
                'running_count' => $statusCounts['running'] ?? 0,
                'waiting_count' => $statusCounts['waiting'] ?? 0,
                'paused_count' => ($statusCounts['suspended'] ?? 0) + ($statusCounts['stopped'] ?? 0),
            ],
            'total_metrics' => [
                'user_count' => $userCount,
                'topic_count' => $topicCount,
            ],
        ];
    }

    public function updateTopicStatus(int $id, $taskId, TaskStatus $status): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'current_task_id' => $taskId,
                'current_task_status' => $status->value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function updateTopicStatusAndSandboxId(int $id, $taskId, TaskStatus $status, string $sandboxId): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'current_task_id' => $taskId,
                'current_task_status' => $status->value,
                'sandbox_id' => $sandboxId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get topic list where last update time exceeds specified time.
     *
     * @param string $timeThreshold Time threshold, topics updated earlier than this will be included in result
     * @param int $limit Maximum number of results to return
     * @return array<TopicEntity> Topic entity list
     */
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array
    {
        $models = $this->model::query()
            ->where('updated_at', '<', $timeThreshold)
            ->where('current_task_status', TaskStatus::RUNNING->value)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($models as $model) {
            $data = $this->convertModelToEntityData($model->toArray());
            $result[] = new TopicEntity($data);
        }

        return $result;
    }

    public function updateTopicStatusBySandboxIds(array $sandboxIds, string $status): bool
    {
        return $this->model::query()
            ->whereIn('sandbox_id', $sandboxIds)
            ->update([
                'current_task_status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get topic list by project ID.
     */
    public function getTopicsByProjectId(int $projectId, string $userId): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $data = $this->convertModelToEntityData($model->toArray());
            $result[] = new TopicEntity($data);
        }

        return $result;
    }

    /**
     * Count topics by project ID.
     */
    public function countTopicsByProjectId(int $projectId): int
    {
        return $this->model::query()
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Batch get workspace IDs with running topics.
     *
     * @param array $workspaceIds Workspace ID array
     * @param null|string $userId Optional user ID, when specified only query topics for this user
     * @return array Workspace ID array with running topics
     */
    public function getRunningWorkspaceIds(array $workspaceIds, ?string $userId = null): array
    {
        if (empty($workspaceIds)) {
            return [];
        }

        $query = $this->model::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->where('current_task_status', TaskStatus::RUNNING->value)
            ->whereNull('deleted_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query
            ->distinct()
            ->pluck('workspace_id')
            ->toArray();
    }

    /**
     * Batch get project IDs with running topics.
     *
     * @param array $projectIds Project ID array
     * @param null|string $userId Optional user ID, when specified only query topics for this user
     * @return array Project ID array with running topics
     */
    public function getRunningProjectIds(array $projectIds, ?string $userId = null): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $query = $this->model::query()
            ->whereIn('project_id', $projectIds)
            ->where('current_task_status', TaskStatus::RUNNING->value)
            ->whereNull('deleted_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query
            ->distinct()
            ->pluck('project_id')
            ->toArray();
    }

    // ======================= Message rollback related methods =======================

    /**
     * Get delightful_message_id by sequence ID.
     */
    public function getDelightfulMessageIdBySeqId(string $seqId): ?string
    {
        $result = $this->delightfulChatSequenceModel::query()
            ->where('id', $seqId)
            ->value('delightful_message_id');

        return $result ?: null;
    }

    /**
     * Get all related seq_ids by delightful_message_id (all perspectives).
     */
    public function getAllSeqIdsByDelightfulMessageId(string $delightfulMessageId): array
    {
        // Return all related seq_ids
        return $this->delightfulChatSequenceModel::query()
            ->where('delightful_message_id', $delightfulMessageId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get all messages from current message onwards in current topic by base seq_ids.
     */
    public function getAllSeqIdsFromCurrent(array $baseSeqIds): array
    {
        if (empty($baseSeqIds)) {
            return [];
        }

        // Batch query conversation_id and topic_id for all baseSeqIds
        $topicInfos = $this->delightfulChatTopicMessageModel::query()
            ->select(['seq_id', 'conversation_id', 'topic_id'])
            ->whereIn('seq_id', $baseSeqIds)
            ->get()
            ->keyBy('seq_id');

        if ($topicInfos->isEmpty()) {
            return [];
        }

        $allSeqIds = [];

        // Iterate through queried topicInfo, get all messages >= seq_id for each topic
        foreach ($topicInfos as $topicInfo) {
            // Query all messages >= this seq_id in the topic (including current message and subsequent messages)
            $seqIds = $this->delightfulChatTopicMessageModel::query()
                ->where('conversation_id', $topicInfo->conversation_id)
                ->where('topic_id', $topicInfo->topic_id)
                ->where('seq_id', '>=', $topicInfo->seq_id)
                ->pluck('seq_id')
                ->toArray();

            $allSeqIds[] = $seqIds;
        }
        ! empty($allSeqIds) && $allSeqIds = array_merge(...$allSeqIds);
        return array_values(array_unique($allSeqIds));
    }

    /**
     * Delete topic_messages data.
     */
    public function deleteTopicMessages(array $seqIds): int
    {
        if (empty($seqIds)) {
            return 0;
        }

        return $this->delightfulChatTopicMessageModel::query()
            ->whereIn('seq_id', $seqIds)
            ->delete();
    }

    /**
     * Delete messages and sequences data by seq_ids.
     */
    public function deleteMessagesAndSequencesBySeqIds(array $seqIds): bool
    {
        if (empty($seqIds)) {
            return true;
        }

        // Get all related delightful_message_ids
        $delightfulMessageIds = $this->delightfulChatSequenceModel::query()
            ->whereIn('id', $seqIds)
            ->distinct()
            ->pluck('delightful_message_id')
            ->toArray();

        // Delete delightful_chat_messages
        if (! empty($delightfulMessageIds)) {
            $this->delightfulMessageModel::query()
                ->whereIn('delightful_message_id', $delightfulMessageIds)
                ->delete();
        }

        // Delete delightful_chat_sequences
        $this->delightfulChatSequenceModel::query()
            ->whereIn('id', $seqIds)
            ->delete();

        return true;
    }

    /**
     * Delete subsequent messages in corresponding topic in delightful_be_agent_message table by im_seq_id.
     */
    public function deleteBeAgentMessagesFromSeqId(int $seqId): int
    {
        // 1. Query corresponding message record by seq_id
        $targetMessage = TaskMessageModel::query()
            ->where('im_seq_id', $seqId)
            ->first(['id', 'topic_id']);

        if (! $targetMessage) {
            return 0;
        }

        $messageId = (int) $targetMessage->id;
        $topicId = (int) $targetMessage->topic_id;

        // 2. Delete all data where id >= messageId in current topic
        return TaskMessageModel::query()
            ->where('topic_id', $topicId)
            ->where('id', '>=', $messageId)
            ->delete();
    }

    /**
     * Batch update status field in delightful_chat_sequences table.
     */
    public function batchUpdateSeqStatus(array $seqIds, DelightfulMessageStatus $status): bool
    {
        if (empty($seqIds)) {
            return true;
        }

        return (bool) $this->delightfulChatSequenceModel::query()
            ->whereIn('id', $seqIds)
            ->update(['status' => $status->value]);
    }

    /**
     * Get all messages before specified seq_id in current topic by base seq_ids.
     */
    public function getAllSeqIdsBeforeCurrent(array $baseSeqIds): array
    {
        if (empty($baseSeqIds)) {
            return [];
        }

        // Batch query conversation_id and topic_id for all baseSeqIds
        $topicInfos = $this->delightfulChatTopicMessageModel::query()
            ->select(['seq_id', 'conversation_id', 'topic_id'])
            ->whereIn('seq_id', $baseSeqIds)
            ->get()
            ->keyBy('seq_id');

        if ($topicInfos->isEmpty()) {
            return [];
        }

        $allSeqIds = [];

        // Iterate through queried topicInfo, get all messages < seq_id for each topic
        foreach ($topicInfos as $topicInfo) {
            // Query all messages < this seq_id in the topic
            $seqIds = $this->delightfulChatTopicMessageModel::query()
                ->where('conversation_id', $topicInfo->conversation_id)
                ->where('topic_id', $topicInfo->topic_id)
                ->where('seq_id', '<', $topicInfo->seq_id)
                ->pluck('seq_id')
                ->toArray();

            $allSeqIds[] = $seqIds;
        }

        ! empty($allSeqIds) && $allSeqIds = array_merge(...$allSeqIds);
        return array_values(array_unique($allSeqIds));
    }

    /**
     * Get all revoked status message seq_ids by topic ID.
     */
    public function getRevokedSeqIdsByTopicId(int $topicId, string $userId): array
    {
        // First get BeAgent topic entity
        $topic = $this->getTopicById($topicId);
        if (! $topic) {
            return [];
        }

        // Get corresponding chat topic ID
        $chatTopicId = $topic->getChatTopicId();
        if (empty($chatTopicId)) {
            return [];
        }

        // Use chat topic ID to query all revoked status messages in this topic
        return $this->delightfulChatTopicMessageModel::query()
            ->join('delightful_chat_sequences', 'delightful_chat_topic_messages.seq_id', '=', 'delightful_chat_sequences.id')
            ->where('delightful_chat_topic_messages.topic_id', $chatTopicId)
            ->where('delightful_chat_sequences.status', DelightfulMessageStatus::Revoked->value)
            ->pluck('delightful_chat_topic_messages.seq_id')
            ->toArray();
    }

    /**
     * Batch get topic names by IDs.
     */
    public function getTopicNamesBatch(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        $results = $this->model::query()
            ->whereIn('id', $topicIds)
            ->whereNull('deleted_at')
            ->select(['id', 'topic_name'])
            ->get();

        $topicNames = [];
        foreach ($results as $result) {
            $topicNames[(string) $result->id] = $result->topic_name;
        }

        return $topicNames;
    }

    /**
     * Convert database model data to entity data.
     * @param array $modelData Model data
     * @return array Entity data
     */
    private function convertModelToEntityData(array $modelData): array
    {
        // Convert snake_case to camelCase
        $entityData = [];
        foreach ($modelData as $key => $value) {
            $camelKey = $this->snakeToCamel($key);
            $entityData[$camelKey] = $value;
        }
        return $entityData;
    }

    /**
     * Convert snake_case to camelCase.
     * Example: user_id => userId, topic_name => topicName.
     *
     * @param string $snake Snake_case string
     * @return string CamelCase string
     */
    private function snakeToCamel(string $snake): string
    {
        // Handle both hyphen and underscore cases
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $snake))));
    }
}
