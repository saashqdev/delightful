<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\Repository\LongTermMemory;
    /**
     * General query method (via DTO).
     */
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryCategory;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryStatus;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryType;
use App\Domain\LongTermMemory\Repository\LongTermMemoryRepositoryInterface;
use App\Infrastructure\Repository\LongTermMemory\Model\LongTermMemoryModel;
use Exception;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Ramsey\Uuid\Uuid;

/**
 * MySQL-backed repository for long-term memory.
 */
class MySQLLongTermMemoryRepository implements LongTermMemoryRepositoryInterface
{
    public function __construct(protected LongTermMemoryModel $model)
    {
    }

    /**
     * Find a memory by ID.
     */
    public function findById(string $id): ?LongTermMemoryEntity
    {
        $model = $this->query()
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        return $model ? $this->entityFromArray($model->toArray()) : null;
    }

    /**
     * Find multiple memories by ID list.
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $data = $this->query()
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * General query method (via DTO).
     */
    public function findMemories(MemoryQueryDTO $dto): array
    {
        $query = $this->query()
            ->where('org_id', $dto->orgId)
            ->where('app_id', $dto->appId)
            ->where('user_id', $dto->userId)
            ->whereNull('deleted_at');

        // Filter by status
        if ($dto->status !== null && ! empty($dto->status)) {
            if (is_array($dto->status)) {
                $query->whereIn('status', $dto->status);
            } else {
                $query->where('status', $dto->status);
            }
        }

        // Filter by type
        if ($dto->type !== null) {
            $query->where('memory_type', $dto->type->value);
        }

        // Filter by project ID
        if ($dto->projectId !== null) {
            $query->where('project_id', $dto->projectId);
        }

        // Filter by enabled flag
        if ($dto->enabled !== null) {
            $query->where('enabled', $dto->enabled ? 1 : 0);
        }

        // Filter by tags
        if (! empty($dto->tags)) {
            foreach ($dto->tags as $tag) {
                $query->whereRaw('JSON_CONTAINS(tags, ?)', [Json::encode($tag)]);
            }
        }

        // Keyword search
        if ($dto->keyword !== null) {
            $query->where(function (Builder $subQuery) use ($dto) {
                $subQuery->where('content', 'like', "%{$dto->keyword}%")
                    ->orWhere('explanation', 'like', "%{$dto->keyword}%");
            });
        }

        // Simple pagination via offset
        if ($dto->offset > 0) {
            $query->offset($dto->offset);
        }

        // Sort
        $query->orderBy($dto->orderBy, $dto->orderDirection);
        // Add ID as secondary sort key for consistent results
        if ($dto->orderBy !== 'id') {
            $query->orderBy('id', $dto->orderDirection);
        }

        // Limit rows
        if ($dto->limit > 0) {
            $query->limit($dto->limit);
        }

        $data = $query->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Count memories by query conditions.
     */
    public function countMemories(MemoryQueryDTO $dto): int
    {
        $query = $this->query()
            ->where('org_id', $dto->orgId)
            ->where('app_id', $dto->appId)
            ->where('user_id', $dto->userId)
            ->whereNull('deleted_at');

        // Filter by status
        if ($dto->status !== null && ! empty($dto->status)) {
            if (is_array($dto->status)) {
                $query->whereIn('status', $dto->status);
            } else {
                $query->where('status', $dto->status);
            }
        }

        // Filter by type
        if ($dto->type !== null) {
            $query->where('memory_type', $dto->type->value);
        }

        // Filter by project ID
        if ($dto->projectId !== null) {
            $query->where('project_id', $dto->projectId);
        }

        // Filter by enabled flag
        if ($dto->enabled !== null) {
            $query->where('enabled', $dto->enabled ? 1 : 0);
        }

        // Filter by tags
        if (! empty($dto->tags)) {
            foreach ($dto->tags as $tag) {
                $query->whereRaw('JSON_CONTAINS(tags, ?)', [Json::encode($tag)]);
            }
        }

        // Keyword search
        if ($dto->keyword !== null) {
            $query->where(function (Builder $subQuery) use ($dto) {
                $subQuery->where('content', 'like', "%{$dto->keyword}%")
                    ->orWhere('explanation', 'like', "%{$dto->keyword}%");
            });
        }

        return $query->count();
    }

    /**
     * Find memories by org, app, and user.
     */
    public function findByUser(string $orgId, string $appId, string $userId, ?string $status = null): array
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Find effective memories by org/app/user (sorted by score).
     */
    public function findEffectiveMemoriesByUser(string $orgId, string $appId, string $userId, string $projectId, int $limit = 50): array
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereIn('status', [MemoryStatus::ACTIVE->value, MemoryStatus::PENDING_REVISION->value])
            ->where('enabled', 1) // Only fetch enabled memories
            ->whereNull('deleted_at')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            });

        // Filter memories by projectId
        if (empty($projectId)) {
            // Fetch memories without projectId (global memories)
            $query->whereNull('project_id');
        } else {
            // Fetch memories for the specified projectId
            $query->where('project_id', $projectId);
        }

        $data = $query->get();

        // Convert to entities
        $entities = $this->entitiesFromArray($data->toArray());

        // Sort by effective score in PHP (descending)
        usort($entities, function ($a, $b) {
            return $b->getEffectiveScore() <=> $a->getEffectiveScore();
        });

        // Apply limit after sorting
        return array_slice($entities, 0, $limit);
    }

    /**
     * Find memories by tags.
     */
    public function findByTags(string $orgId, string $appId, string $userId, array $tags, ?string $status = null): array
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        foreach ($tags as $tag) {
            $query->whereRaw('JSON_CONTAINS(tags, ?)', [Json::encode($tag)]);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Find memories by type.
     */
    public function findByType(string $orgId, string $appId, string $userId, MemoryType $type, ?string $status = null): array
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->where('memory_type', $type->value)
            ->whereNull('deleted_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Search memories by content keyword.
     */
    public function searchByContent(string $orgId, string $appId, string $userId, string $keyword, ?string $status = null): array
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($keyword) {
                $query->where('content', 'like', "%{$keyword}%");
            });

        if ($status !== null) {
            $query->where('status', $status);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Find memories to evict.
     */
    public function findMemoriesToEvict(string $orgId, string $appId, string $userId): array
    {
        $data = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) {
                $query->where('expires_at', '<', date('Y-m-d H:i:s'))
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('importance', '<', 0.2)
                            ->where('last_accessed_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')));
                    })
                    ->orWhereRaw('(importance * confidence * decay_factor) < 0.1');
            })
            ->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Find memories to compress.
     */
    public function findMemoriesToCompress(string $orgId, string $appId, string $userId): array
    {
        $data = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->whereRaw('CHAR_LENGTH(content) > 1000')
                        ->where('importance', '<', 0.6);
                })
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('last_accessed_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
                            ->whereRaw('(importance * confidence * decay_factor) >= 0.1');
                    });
            })
            ->get();

        return $this->entitiesFromArray($data->toArray());
    }

    /**
     * Save a memory.
     */
    public function save(LongTermMemoryEntity $memory): bool
    {
        $data = $this->entityToArray($memory);

        // Generate ID when missing
        if (empty($data['id'])) {
            $data['id'] = Uuid::uuid4()->toString();
            $memory->setId($data['id']);
        }

        return $this->query()->insert($data);
    }

    /**
     * Save memories in batch.
     */
    public function saveBatch(array $memories): bool
    {
        $data = [];
        foreach ($memories as $memory) {
            $memoryData = $this->entityToArray($memory);

            // Generate ID when missing
            if (empty($memoryData['id'])) {
                $memoryData['id'] = Uuid::uuid4()->toString();
                $memory->setId($memoryData['id']);
            }

            $data[] = $memoryData;
        }

        return $this->query()->insert($data);
    }

    /**
     * Update a memory.
     */
    public function update(LongTermMemoryEntity $memory): bool
    {
        $data = $this->entityToArray($memory);
        unset($data['id']); // Keep ID unchanged

        return $this->query()
            ->where('id', $memory->getId())
            ->update($data) > 0;
    }

    /**
     * Update memories in batch.
     * @param array<LongTermMemoryEntity> $memories List of memory entities
     */
    public function updateBatch(array $memories): bool
    {
        if (empty($memories)) {
            return true;
        }

        return Db::transaction(function () use ($memories) {
            $table = $this->model->getTable();

            // Convert entities to array rows
            $dataArray = [];
            foreach ($memories as $memory) {
                $dataArray[] = $this->entityToArray($memory);
            }

            // Infer field list from the first row
            $fields = array_keys($dataArray[0]);

            // Build VALUES section
            $valueRows = [];
            $bindings = [];

            foreach ($dataArray as $data) {
                $placeholders = [];
                foreach ($fields as $field) {
                    $placeholders[] = '?';
                    $bindings[] = $data[$field] ?? null;
                }
                $valueRows[] = '(' . implode(', ', $placeholders) . ')';
            }

            // Build REPLACE INTO SQL
            $fieldsStr = implode(', ', $fields);
            $valuesStr = implode(', ', $valueRows);
            $sql = "REPLACE INTO {$table} ({$fieldsStr}) VALUES {$valuesStr}";

            $result = Db::statement($sql, $bindings);

            if (! $result) {
                throw new Exception('Failed to batch replace memories');
            }

            return true;
        });
    }

    /**
     * Delete a memory.
     */
    public function delete(string $id): bool
    {
        return $this->query()
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Delete memories in batch.
     */
    public function deleteBatch(array $ids): bool
    {
        return $this->query()
            ->whereIn('id', $ids)
            ->delete() > 0;
    }

    /**
     * Delete memories by project ID.
     */
    public function deleteByProjectId(string $orgId, string $appId, string $userId, string $projectId): int
    {
        return $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->delete();
    }

    /**
     * Delete memories by project ID list.
     */
    public function deleteByProjectIds(string $orgId, string $appId, string $userId, array $projectIds): int
    {
        if (empty($projectIds)) {
            return 0;
        }

        return $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereIn('project_id', $projectIds)
            ->delete();
    }

    /**
     * Soft delete a memory.
     */
    public function softDelete(string $id): bool
    {
        return $this->query()
            ->where('id', $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * Count memories for a user.
     */
    public function countByUser(string $orgId, string $appId, string $userId): int
    {
        return $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Count memories per type for a user.
     */
    public function countByUserAndType(string $orgId, string $appId, string $userId): array
    {
        $data = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('memory_type, COUNT(*) as count')
            ->groupBy('memory_type')
            ->get();

        $result = [];
        foreach ($data as $row) {
            $result[$row['memory_type']] = $row['count'];
        }

        return $result;
    }

    /**
     * Get total memory size (characters) for a user.
     */
    public function getTotalSizeByUser(string $orgId, string $appId, string $userId): int
    {
        $result = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(CHAR_LENGTH(content)) as total_size')
            ->first();
        if (! $result) {
            return 0;
        }
        return $result['total_size'] ?? 0;
    }

    /**
     * Filter memory IDs that belong to the user.
     */
    public function filterMemoriesByUser(array $memoryIds, string $orgId, string $appId, string $userId): array
    {
        if (empty($memoryIds)) {
            return [];
        }

        return $this->query()
            ->whereIn('id', $memoryIds)
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Batch update memory enabled flag.
     */
    public function batchUpdateEnabled(array $memoryIds, bool $enabled, string $orgId, string $appId, string $userId): int
    {
        if (empty($memoryIds)) {
            return 0;
        }

        // Only update memories in active status
        return LongTermMemoryModel::query()
            ->whereIn('id', $memoryIds)
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->where('status', 'active') // Only effective memories can be toggled
            ->whereNull('deleted_at')
            ->update([
                'enabled' => $enabled ? 1 : 0,
            ]);
    }

    /**
     * Get the enabled memory count for a given category.
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @param MemoryCategory $category Memory category
     * @return int Memory count
     */
    public function getEnabledMemoryCountByCategory(string $orgId, string $appId, string $userId, MemoryCategory $category): int
    {
        $query = $this->query()
            ->where('org_id', $orgId)
            ->where('app_id', $appId)
            ->where('user_id', $userId)
            ->where('enabled', 1)
            ->where('status', MemoryStatus::ACTIVE->value)
            ->whereNull('deleted_at');

        if ($category === MemoryCategory::PROJECT) {
            // Project memories: project_id is not null/empty
            $query->whereNotNull('project_id')
                ->where('project_id', '!=', '');
        } else {
            // Global memories: project_id is null or empty
            $query->where(function ($subQuery) {
                $subQuery->whereNull('project_id')
                    ->orWhere('project_id', '');
            });
        }

        return $query->count();
    }

    /**
     * Get query builder.
     */
    private function query(): Builder
    {
        return $this->model::query();
    }

    /**
     * Convert entity to array for storage.
     */
    private function entityToArray(LongTermMemoryEntity $memory): array
    {
        return [
            'id' => $memory->getId(),
            'content' => $memory->getContent(),
            'pending_content' => $memory->getPendingContent(),
            'explanation' => $memory->getExplanation(),
            'origin_text' => $memory->getOriginText(),
            'memory_type' => $memory->getMemoryType()->value,
            'status' => $memory->getStatus()->value,
            'enabled' => $memory->isEnabled() ? 1 : 0, // Convert boolean to DB-friendly value
            'confidence' => $memory->getConfidence(),
            'importance' => $memory->getImportance(),
            'access_count' => $memory->getAccessCount(),
            'reinforcement_count' => $memory->getReinforcementCount(),
            'decay_factor' => $memory->getDecayFactor(),
            'tags' => Json::encode($memory->getTags()),
            'metadata' => Json::encode($memory->getMetadata()),
            'org_id' => $memory->getOrgId(),
            'app_id' => $memory->getAppId(),
            'project_id' => $memory->getProjectId(),
            'user_id' => $memory->getUserId(),
            'last_accessed_at' => $memory->getLastAccessedAt()?->format('Y-m-d H:i:s'),
            'last_reinforced_at' => $memory->getLastReinforcedAt()?->format('Y-m-d H:i:s'),
            'expires_at' => $memory->getExpiresAt()?->format('Y-m-d H:i:s'),
            'created_at' => $memory->getCreatedAt()?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => $memory->getDeletedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Convert array to entity.
     */
    private function entityFromArray(array $data): LongTermMemoryEntity
    {
        return new LongTermMemoryEntity($data);
    }

    /**
     * Convert array rows to entity list.
     * @return LongTermMemoryEntity[]
     */
    private function entitiesFromArray(array $dataArray): array
    {
        return array_map(function ($data) {
            return $this->entityFromArray((array) $data);
        }, $dataArray);
    }
}
