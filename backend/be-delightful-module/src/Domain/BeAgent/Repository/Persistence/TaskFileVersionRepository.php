<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileVersionEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileVersionRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskFileVersionModel;

class TaskFileVersionRepository implements TaskFileVersionRepositoryInterface
{
    public function __construct(protected TaskFileVersionModel $model)
    {
    }

    public function getById(int $id): ?TaskFileVersionEntity
    {
        $model = $this->model::query()->where('id', $id)->first();
        if (! $model) {
            return null;
        }
        return new TaskFileVersionEntity($model->toArray());
    }

    public function getByFileId(int $fileId): array
    {
        $models = $this->model::query()
            ->where('file_id', $fileId)
            ->orderBy('version', 'desc')
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileVersionEntity($model->toArray());
        }

        return $entities;
    }

    public function countByFileId(int $fileId): int
    {
        return $this->model::query()
            ->where('file_id', $fileId)
            ->count();
    }

    public function getLatestVersionNumber(int $fileId): int
    {
        $latestVersion = $this->model::query()
            ->where('file_id', $fileId)
            ->max('version');

        return $latestVersion ?? 0;
    }

    public function insert(TaskFileVersionEntity $entity): TaskFileVersionEntity
    {
        $date = date('Y-m-d H:i:s');
        $entity->setCreatedAt($date);
        $entity->setUpdatedAt($date);

        $entityArray = $entity->toArray();
        $model = $this->model::query()->create($entityArray);

        if (! empty($model->id)) {
            $entity->setId($model->id);
        }

        return $entity;
    }

    public function deleteOldVersionsByFileId(int $fileId, int $keepCount): int
    {
        // Get the list of version entities to clean up
        $versionsToDelete = $this->getVersionsToCleanup($fileId, $keepCount);

        if (empty($versionsToDelete)) {
            return 0;
        }

        // Extract version IDs for batch deletion
        $idsToDelete = array_map(fn ($version) => $version->getId(), $versionsToDelete);

        // Batch delete old versions
        return $this->model::query()
            ->whereIn('id', $idsToDelete)
            ->delete();
    }

    public function deleteAllVersionsByFileId(int $fileId): int
    {
        return $this->model::query()
            ->where('file_id', $fileId)
            ->delete();
    }

    /**
     * Get the list of version entities to clean up.
     */
    public function getVersionsToCleanup(int $fileId, int $keepCount): array
    {
        // Step 1: Get version IDs to keep (latest keepCount versions)
        $idsToKeep = $this->model::query()
            ->where('file_id', $fileId)
            ->orderBy('version', 'desc')
            ->limit($keepCount)
            ->pluck('id')
            ->toArray();

        if (empty($idsToKeep)) {
            // If there are no versions to keep, return all versions for cleanup
            $models = $this->model::query()
                ->where('file_id', $fileId)
                ->get();
        } else {
            // Step 2: Get version records to delete
            $models = $this->model::query()
                ->where('file_id', $fileId)
                ->whereNotIn('id', $idsToKeep)
                ->get();
        }

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileVersionEntity($model->toArray());
        }

        return $entities;
    }

    /**
     * Get paginated version list for specified file, sorted by version number in descending order.
     */
    public function getByFileIdWithPage(int $fileId, int $page, int $pageSize): array
    {
        $query = $this->model::query()->where('file_id', $fileId);

        // Get total count
        $total = $query->count();

        // Paginated query
        $models = $query->orderBy('version', 'desc')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        // Convert to entities
        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileVersionEntity($model->toArray());
        }

        return [
            'list' => $entities,
            'total' => $total,
        ];
    }

    /**
     * Get specific version by file ID and version number.
     */
    public function getByFileIdAndVersion(int $fileId, int $version): ?TaskFileVersionEntity
    {
        $model = $this->model::query()
            ->where('file_id', $fileId)
            ->where('version', $version)
            ->first();

        if (! $model) {
            return null;
        }

        return new TaskFileVersionEntity($model->toArray());
    }
}
