<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;
use Delightful\BeDelightful\Domain\BeAgent\Entity\WorkspaceEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\WorkspaceRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\WorkspaceModel;
use Hyperf\DbConnection\Db;

class WorkspaceRepository extends AbstractRepository implements WorkspaceRepositoryInterface
{
    public function __construct(protected WorkspaceModel $model)
    {
    }

    /**
     * Get user workspace list.
     */
    public function getUserWorkspaces(string $userId, int $page, int $pageSize): array
    {
        $models = $this->model::query()
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = $this->modelToEntity($model);
        }

        return $entities;
    }

    /**
     * Create workspace.
     */
    public function createWorkspace(WorkspaceEntity $workspaceEntity): WorkspaceEntity
    {
        $model = new $this->model();
        $model->fill($workspaceEntity->toArray());
        $model->save();

        $workspaceEntity->setId($model->id);
        return $workspaceEntity;
    }

    /**
     * Update workspace.
     */
    public function updateWorkspace(WorkspaceEntity $workspaceEntity): bool
    {
        $model = $this->model::query()->find($workspaceEntity->getId());
        if (! $model) {
            return false;
        }

        return $model->update($workspaceEntity->toArray());
    }

    /**
     * Get workspace details.
     */
    public function getWorkspaceById(int $workspaceId): ?WorkspaceEntity
    {
        $model = $this->model::query()->where('id', $workspaceId)->whereNull('deleted_at')->first();
        return $this->modelToEntity($model);
    }

    /**
     * Find workspace by ID.
     */
    public function findById(int $workspaceId): ?WorkspaceEntity
    {
        return $this->getWorkspaceById($workspaceId);
    }

    /**
     * Get workspace by conversation ID.
     */
    public function getWorkspaceByConversationId(string $conversationId): ?WorkspaceEntity
    {
        $model = $this->model::query()->where('conversation_id', $conversationId)->whereNull('deleted_at')->first();
        return $this->modelToEntity($model);
    }

    /**
     * Update workspace archived status.
     */
    public function updateWorkspaceArchivedStatus(int $workspaceId, int $isArchived): bool
    {
        return $this->model::query()
            ->where('id', $workspaceId)
            ->update(['is_archived' => $isArchived]) > 0;
    }

    /**
     * Delete workspace.
     */
    public function deleteWorkspace(int $workspaceId): bool
    {
        return $this->model::query()->where('id', $workspaceId)->delete() > 0;
    }

    /**
     * Delete topics associated with workspace.
     */
    public function deleteTopicsByWorkspaceId(int $workspaceId): bool
    {
        // Note: This needs to be implemented based on actual situation, such as deleting topics through external service or other Repository
        // Since we don't see the structure of the topic table, this is just an example
        return Db::table('delightful_chat_topics')
            ->where('workspace_id', $workspaceId)
            ->delete() > 0;
    }

    /**
     * Update workspace current topic.
     */
    public function updateWorkspaceCurrentTopic(int $workspaceId, string $topicId): bool
    {
        return $this->model::query()
            ->where('id', $workspaceId)
            ->update(['current_topic_id' => $topicId]) > 0;
    }

    /**
     * Update workspace status.
     */
    public function updateWorkspaceStatus(int $workspaceId, int $status): bool
    {
        return $this->model::query()
            ->where('id', $workspaceId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Get workspace list by conditions
     * Supports pagination and sorting.
     *
     * @param array $conditions Query conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param string $orderBy Sort field
     * @param string $orderDirection Sort direction
     * @return array [total, list] Total count and workspace list
     */
    public function getWorkspacesByConditions(
        array $conditions = [],
        int $page = 1,
        int $pageSize = 10,
        string $orderBy = 'id',
        string $orderDirection = 'asc'
    ): array {
        $query = $this->model::query();

        // Filter deleted data by default
        $query->whereNull('deleted_at');

        // Apply query conditions
        foreach ($conditions as $field => $value) {
            // Default equal query
            $query->where($field, $value);
        }

        // Get total count
        $total = $query->count();

        // Sort and paginate
        $list = $query->orderBy($orderBy, $orderDirection)
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        // Convert to entity objects
        $entities = [];
        foreach ($list as $model) {
            $entities[] = $this->modelToEntity($model);
        }

        return [
            'total' => $total,
            'list' => $entities,
        ];
    }

    /**
     * Save workspace (create or update).
     *
     * @param WorkspaceEntity $workspaceEntity Workspace entity
     * @return WorkspaceEntity Saved workspace entity
     */
    public function save(WorkspaceEntity $workspaceEntity): WorkspaceEntity
    {
        if ($workspaceEntity->getId()) {
            // Update existing workspace
            $model = $this->model::query()->find($workspaceEntity->getId());
            if ($model) {
                $model->update($workspaceEntity->toArray());
            }
        } else {
            // Create new workspace
            $model = new $this->model();
            $model->fill($workspaceEntity->toArray());
            $model->save();
            $workspaceEntity->setId($model->id);
        }

        return $workspaceEntity;
    }

    /**
     * Get unique organization code list for all workspaces.
     *
     * @return array Unique organization code list
     */
    public function getUniqueOrganizationCodes(): array
    {
        return $this->model::query()
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('user_organization_code')
            ->filter(function ($code) {
                return ! empty($code);
            })
            ->toArray();
    }

    /**
     * Batch get workspace name mapping.
     *
     * @param array $workspaceIds Workspace ID array
     * @return array ['workspace_id' => 'workspace_name'] Key-value pairs
     */
    public function getWorkspaceNamesBatch(array $workspaceIds): array
    {
        if (empty($workspaceIds)) {
            return [];
        }

        $results = $this->model::query()
            ->whereIn('id', $workspaceIds)
            ->whereNull('deleted_at')
            ->select(['id', 'name'])
            ->get();

        $workspaceNames = [];
        foreach ($results as $result) {
            $workspaceNames[(string) $result->id] = $result->name;
        }

        return $workspaceNames;
    }

    /**
     * Convert model object to entity object
     *
     * @param null|WorkspaceModel $model Model object
     * @return null|WorkspaceEntity Entity object
     */
    protected function modelToEntity($model): ?WorkspaceEntity
    {
        if (! $model) {
            return null;
        }

        $entity = new WorkspaceEntity();
        $entity->setId((int) $model->id);
        $entity->setUserId((string) $model->user_id);
        $entity->setUserOrganizationCode((string) $model->user_organization_code);
        $entity->setChatConversationId((string) $model->chat_conversation_id);
        $entity->setName((string) $model->name);
        $entity->setIsArchived((int) $model->is_archived);
        $entity->setCreatedUid((string) $model->created_uid);
        $entity->setUpdatedUid((string) $model->updated_uid);
        $entity->setCreatedAt($model->created_at ? (string) $model->created_at : null);
        $entity->setUpdatedAt($model->updated_at ? (string) $model->updated_at : null);
        $entity->setDeletedAt($model->deleted_at ? (string) $model->deleted_at : null);
        $entity->setCurrentTopicId($model->current_topic_id ? (int) $model->current_topic_id : null);
        $entity->setStatus((int) $model->status);

        return $entity;
    }
}
