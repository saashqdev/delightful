<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberSettingEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectMemberSettingRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\ProjectMemberSettingModel;

/**
 * Project member setting repository implementation.
 *
 * Responsible for data persistence operations of project member settings
 */
class ProjectMemberSettingRepository implements ProjectMemberSettingRepositoryInterface
{
    public function __construct(
        private readonly ProjectMemberSettingModel $model
    ) {
    }

    /**
     * Find setting by user ID and project ID.
     */
    public function findByUserAndProject(string $userId, int $projectId): ?ProjectMemberSettingEntity
    {
        $result = $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if (! $result) {
            return null;
        }

        return ProjectMemberSettingEntity::modelToEntity($result->toArray());
    }

    /**
     * Create project member setting.
     */
    public function create(string $userId, int $projectId, string $organizationCode): ProjectMemberSettingEntity
    {
        $now = date('Y-m-d H:i:s');
        $attributes = [
            'id' => IdGenerator::getSnowId(),
            'user_id' => $userId,
            'project_id' => $projectId,
            'organization_code' => $organizationCode,
            'is_pinned' => 0,
            'pinned_at' => null,
            'last_active_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->model::query()->create($attributes);

        return ProjectMemberSettingEntity::modelToEntity($attributes);
    }

    /**
     * Create or update project member setting.
     */
    public function save(ProjectMemberSettingEntity $entity): ProjectMemberSettingEntity
    {
        $attributes = $entity->toInsertArray();
        $now = date('Y-m-d H:i:s');

        // If entity has no ID, it's a new record
        if ($entity->getId() === 0) {
            $attributes['id'] = IdGenerator::getSnowId();
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;

            // Use ON DUPLICATE KEY UPDATE to handle concurrency
            $this->model::query()->insertOrUpdate($attributes, [
                'is_pinned' => $attributes['is_pinned'],
                'pinned_at' => $attributes['pinned_at'] ?? null,
                'last_active_at' => $attributes['last_active_at'],
                'updated_at' => $now,
            ]);

            $entity->setId($attributes['id']);
        } else {
            // Update existing record
            $this->model::query()
                ->where('id', $entity->getId())
                ->update([
                    'is_pinned' => $attributes['is_pinned'],
                    'pinned_at' => $attributes['pinned_at'] ?? null,
                    'last_active_at' => $attributes['last_active_at'],
                    'updated_at' => $now,
                ]);
        }

        return $entity;
    }

    /**
     * Update pin status (assumes record already exists).
     */
    public function updatePinStatus(string $userId, int $projectId, bool $isPinned): bool
    {
        $now = date('Y-m-d H:i:s');
        $attributes = [
            'is_pinned' => $isPinned ? 1 : 0,
            'pinned_at' => $isPinned ? $now : null,
            'last_active_at' => $now,
            'updated_at' => $now,
        ];

        // Update existing record
        $updated = $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->update($attributes);

        return $updated > 0;
    }

    /**
     * Batch get user's pinned project ID list.
     */
    public function getPinnedProjectIds(string $userId, string $organizationCode): array
    {
        $results = $this->model::query()
            ->where('user_id', $userId)
            ->where('organization_code', $organizationCode)
            ->where('is_pinned', 1)
            ->orderBy('pinned_at', 'desc')
            ->pluck('project_id')
            ->toArray();

        return array_map(fn ($id) => (int) $id, $results);
    }

    /**
     * Batch get user's settings for multiple projects.
     */
    public function findByUserAndProjects(string $userId, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $results = $this->model::query()
            ->where('user_id', $userId)
            ->whereIn('project_id', $projectIds)
            ->get()
            ->keyBy('project_id')
            ->toArray();

        $entities = [];
        foreach ($results as $projectId => $data) {
            $entities[(int) $projectId] = ProjectMemberSettingEntity::modelToEntity($data);
        }

        return $entities;
    }

    /**
     * Update last active time.
     */
    public function updateLastActiveTime(string $userId, int $projectId): bool
    {
        $now = date('Y-m-d H:i:s');
        $attributes = [
            'last_active_at' => $now,
        ];

        return (bool) $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->update($attributes);
    }

    /**
     * Delete all settings related to project.
     */
    public function deleteByProjectId(int $projectId): int
    {
        return $this->model::query()
            ->where('project_id', $projectId)
            ->delete();
    }

    /**
     * Delete all settings related to user.
     */
    public function deleteByUser(string $userId, string $organizationCode): int
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->where('organization_code', $organizationCode)
            ->delete();
    }

    /**
     * Set project shortcut (bind to workspace).
     */
    public function setProjectShortcut(string $userId, int $projectId, int $workspaceId, string $organizationCode): bool
    {
        $now = date('Y-m-d H:i:s');

        // Check if record exists
        $existing = $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($existing) {
            // Update existing record
            return (bool) $this->model::query()
                ->where('user_id', $userId)
                ->where('project_id', $projectId)
                ->update([
                    'is_bind_workspace' => 1,
                    'bind_workspace_id' => $workspaceId,
                    'last_active_at' => $now,
                    'updated_at' => $now,
                ]);
        }
        // Create new record
        $attributes = [
            'id' => IdGenerator::getSnowId(),
            'user_id' => $userId,
            'project_id' => $projectId,
            'organization_code' => $organizationCode,
            'is_pinned' => 0,
            'pinned_at' => null,
            'is_bind_workspace' => 1,
            'bind_workspace_id' => $workspaceId,
            'last_active_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->model::query()->create($attributes);
        return true;
    }

    /**
     * Cancel project shortcut (unbind from workspace).
     */
    public function cancelProjectShortcut(string $userId, int $projectId): bool
    {
        $now = date('Y-m-d H:i:s');

        return (bool) $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->update([
                'is_bind_workspace' => 0,
                'bind_workspace_id' => 0,
                'last_active_at' => $now,
                'updated_at' => $now,
            ]);
    }

    /**
     * Check if project shortcut is set.
     */
    public function hasProjectShortcut(string $userId, int $projectId, int $workspaceId): bool
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('is_bind_workspace', 1)
            ->where('bind_workspace_id', $workspaceId)
            ->exists();
    }
}
