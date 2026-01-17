<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileVersionEntity;

interface TaskFileVersionRepositoryInterface
{
    /**
     * Get file version by ID.
     */
    public function getById(int $id): ?TaskFileVersionEntity;

    /**
     * Get all version list by file ID, sorted by version number in descending order.
     *
     * @return TaskFileVersionEntity[]
     */
    public function getByFileId(int $fileId): array;

    /**
     * Get version count for specified file.
     */
    public function countByFileId(int $fileId): int;

    /**
     * Get latest version number for specified file.
     */
    public function getLatestVersionNumber(int $fileId): int;

    /**
     * Insert file version.
     */
    public function insert(TaskFileVersionEntity $entity): TaskFileVersionEntity;

    /**
     * Delete old versions that exceed count limit by file ID.
     */
    public function deleteOldVersionsByFileId(int $fileId, int $keepCount): int;

    /**
     * Batch delete all versions by file ID.
     */
    public function deleteAllVersionsByFileId(int $fileId): int;

    /**
     * Get list of version entities to cleanup.
     *
     * @return TaskFileVersionEntity[]
     */
    public function getVersionsToCleanup(int $fileId, int $keepCount): array;

    /**
     * Get version list for specified file with pagination, sorted by version number in descending order.
     *
     * @param int $fileId File ID
     * @param int $page Page number (starting from 1)
     * @param int $pageSize Items per page
     * @return array Array containing list and total
     */
    public function getByFileIdWithPage(int $fileId, int $page, int $pageSize): array;

    /**
     * Get specific version by file ID and version number.
     */
    public function getByFileIdAndVersion(int $fileId, int $version): ?TaskFileVersionEntity;
}
