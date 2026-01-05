<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\LongTermMemory\Repository;

use App\Domain\LongTermMemory\DTO\MemoryQueryDTO;
use App\Domain\LongTermMemory\Entity\LongTermMemoryEntity;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryCategory;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryType;

/**
 * Long-term memory repository interface.
 */
interface LongTermMemoryRepositoryInterface
{
    /**
     * Find memory by ID.
     */
    public function findById(string $id): ?LongTermMemoryEntity;

    /**
     * Find memories by a list of IDs.
     * @param array $ids ID list
     * @return array<LongTermMemoryEntity> List of memory entities
     */
    public function findByIds(array $ids): array;

    /**
     * Generic query method (uses DTO).
     * @return LongTermMemoryEntity[]
     */
    public function findMemories(MemoryQueryDTO $dto): array;

    /**
     * Count memories by query conditions.
     */
    public function countMemories(MemoryQueryDTO $dto): int;

    /**
     * Find all memories by organization, application, and user.
     */
    public function findByUser(string $orgId, string $appId, string $userId, ?string $status = null): array;

    /**
     * Find effective memories by organization, application, and user (sorted by score).
     */
    public function findEffectiveMemoriesByUser(string $orgId, string $appId, string $userId, string $projectId, int $limit = 50): array;

    /**
     * Find memories by tags.
     */
    public function findByTags(string $orgId, string $appId, string $userId, array $tags, ?string $status = null): array;

    /**
     * Find memories by memory type.
     */
    public function findByType(string $orgId, string $appId, string $userId, MemoryType $type, ?string $status = null): array;

    /**
     * Search memories by content keyword.
     */
    public function searchByContent(string $orgId, string $appId, string $userId, string $keyword, ?string $status = null): array;

    /**
     * Find memories to evict.
     */
    public function findMemoriesToEvict(string $orgId, string $appId, string $userId): array;

    /**
     * Find memories that need to be compressed.
     */
    public function findMemoriesToCompress(string $orgId, string $appId, string $userId): array;

    /**
     * Save a memory.
     */
    public function save(LongTermMemoryEntity $memory): bool;

    /**
     * Save memories in bulk.
     */
    public function saveBatch(array $memories): bool;

    /**
     * Update a memory.
     */
    public function update(LongTermMemoryEntity $memory): bool;

    /**
     * Update memories in bulk.
     * @param array<LongTermMemoryEntity> $memories Memory entity list
     * @return bool Whether the update succeeded
     */
    public function updateBatch(array $memories): bool;

    /**
     * Delete a memory.
     */
    public function delete(string $id): bool;

    /**
     * Delete memories in bulk.
     */
    public function deleteBatch(array $ids): bool;

    /**
     * Soft-delete a memory.
     */
    public function softDelete(string $id): bool;

    /**
     * Count memories for a user.
     */
    public function countByUser(string $orgId, string $appId, string $userId): int;

    /**
     * Count memories by type for a user.
     */
    public function countByUserAndType(string $orgId, string $appId, string $userId): array;

    /**
     * Get the total size of a user's memories (character count).
     */
    public function getTotalSizeByUser(string $orgId, string $appId, string $userId): int;

    /**
     * Validate in bulk whether memories belong to a user.
     * @param array $memoryIds Memory ID list
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @return array IDs belonging to the user
     */
    public function filterMemoriesByUser(array $memoryIds, string $orgId, string $appId, string $userId): array;

    /**
     * Update enabled status for multiple memories.
     * @param array $memoryIds Memory ID list
     * @param bool $enabled Enabled status
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @return int Number of updated records
     */
    public function batchUpdateEnabled(array $memoryIds, bool $enabled, string $orgId, string $appId, string $userId): int;

    /**
     * Get the count of enabled memories for a category.
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @param MemoryCategory $category Memory category
     * @return int Memory count
     */
    public function getEnabledMemoryCountByCategory(string $orgId, string $appId, string $userId, MemoryCategory $category): int;

    /**
     * Delete memories by project ID.
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @param string $projectId Project ID
     * @return int Number of deleted records
     */
    public function deleteByProjectId(string $orgId, string $appId, string $userId, string $projectId): int;

    /**
     * Delete memories in bulk by project IDs.
     * @param string $orgId Organization ID
     * @param string $appId Application ID
     * @param string $userId User ID
     * @param array $projectIds Project ID list
     * @return int Number of deleted records
     */
    public function deleteByProjectIds(string $orgId, string $appId, string $userId, array $projectIds): int;
}
