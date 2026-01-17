<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Repository\Facade;

use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;

/**
 * Resource share repository interface.
 */
interface ResourceShareRepositoryInterface
{
    /**
     * Get share by ID.
     *
     * @param int $shareId Share ID
     * @return null|ResourceShareEntity Resource share entity
     */
    public function getShareById(int $shareId): ?ResourceShareEntity;

    /**
     * Get share by share code.
     *
     * @param string $shareCode Share code
     * @return null|ResourceShareEntity Resource share entity
     */
    public function getShareByCode(string $shareCode): ?ResourceShareEntity;

    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity;

    /**
     * Save share entity.
     *
     * @param ResourceShareEntity $shareEntity Resource share entity
     * @return ResourceShareEntity Saved resource share entity
     */
    public function save(ResourceShareEntity $shareEntity): ResourceShareEntity;

    /**
     * Delete share.
     *
     * @param int $shareId Share ID
     * @param bool $forceDelete Whether to force delete (physical delete), default false for soft delete
     * @return bool Whether successful
     */
    public function delete(int $shareId, bool $forceDelete = false): bool;

    /**
     * Increment share view count.
     *
     * @param string $shareCode Share code
     * @return bool Whether successful
     */
    public function incrementViewCount(string $shareCode): bool;

    /**
     * Paginated query.
     *
     * @param array $conditions Query conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Pagination result
     */
    public function paginate(array $conditions, int $page = 1, int $pageSize = 20): array;

    public function getShareByResource(string $userId, string $resourceId, int $resourceType, bool $withTrashed = true): ?ResourceShareEntity;

    /**
     * Check if share code already exists.
     *
     * @param string $shareCode Share code
     * @return bool Whether exists
     */
    public function isShareCodeExists(string $shareCode): bool;
}
