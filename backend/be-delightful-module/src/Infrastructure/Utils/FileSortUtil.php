<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileRepositoryInterface;

/**
 * File sorting utility.
 * Implements file sorting algorithms, following infrastructure layer responsibilities.
 */
class FileSortUtil
{
    public const DEFAULT_SORT_STEP = 10000;

    public const MIN_SORT_GAP = 10;

    public const MAX_SORT_VALUE = 9223372036854775807; // bigint maximum value

    /**
     * Calculate the sort value for a new file.
     *
     * @param TaskFileRepositoryInterface $repository Repository interface
     * @param null|int $parentId Parent directory ID
     * @param int $preFileId Previous file ID; 0 inserts at first position, -1 inserts at end
     * @param int $projectId Project ID (for data isolation)
     * @return int Calculated sort value
     */
    public static function calculateSortValue(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $preFileId,
        int $projectId
    ): int {
        if ($preFileId === 0) {
            // Insert at the first position
            return self::calculateFirstPositionSort($repository, $parentId, $projectId);
        }

        if ($preFileId === -1) {
            // Insert at the end (default behavior)
            return self::calculateLastPositionSort($repository, $parentId, $projectId);
        }

        // Insert after the specified file
        return self::calculateAfterPositionSort($repository, $parentId, $preFileId, $projectId);
    }

    /**
     * Reorder sibling files in batch.
     *
     * @param TaskFileRepositoryInterface $repository Repository interface
     * @param null|int $parentId Parent directory ID
     * @param int $projectId Project ID
     * @return array Sort data requiring updates
     */
    public static function reorderSiblings(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $projectId
    ): array {
        $siblings = $repository->getSiblingsByParentId($parentId, $projectId, 'sort', 'ASC');

        $updates = [];
        foreach ($siblings as $index => $sibling) {
            $newSort = ($index + 1) * self::DEFAULT_SORT_STEP;
            if ($sibling['sort'] !== $newSort) {
                $updates[] = [
                    'file_id' => $sibling['file_id'],
                    'sort' => $newSort,
                ];
            }
        }

        return $updates;
    }

    /**
     * Get the default sort value (for new files without a specified position).
     */
    public static function getDefaultSortValue(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $projectId
    ): int {
        return self::calculateLastPositionSort($repository, $parentId, $projectId);
    }

    /**
     * Validate sort parameters.
     */
    public static function validateSortParams(?int $parentId, int $preFileId, int $projectId): bool
    {
        // No validation needed when preFileId is 0 or -1
        if ($preFileId <= 0) {
            return true;
        }

        // TODO: Add more validation logic, e.g., verify preFileId exists and belongs to the same parent directory
        return true;
    }

    /**
     * Calculate sort value when inserting at the first position.
     */
    private static function calculateFirstPositionSort(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $projectId
    ): int {
        $minSort = $repository->getMinSortByParentId($parentId, $projectId);

        if ($minSort === null) {
            // No files; use default value
            return self::DEFAULT_SORT_STEP;
        }

        if ($minSort > self::DEFAULT_SORT_STEP) {
            // Minimum value is large; use default value to insert at the front
            return self::DEFAULT_SORT_STEP;
        }

        // Try to calculate a smaller value using half the step
        $halfStep = intval(self::DEFAULT_SORT_STEP / 2);
        if ($minSort > $halfStep) {
            return $minSort - $halfStep;
        }

        // If the minimum value is too small to insert reasonably, trigger reordering
        // Reordering should occur here; temporarily return the default value
        return self::DEFAULT_SORT_STEP;
    }

    /**
     * Calculate sort value when inserting at the end.
     */
    private static function calculateLastPositionSort(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $projectId
    ): int {
        $maxSort = $repository->getMaxSortByParentId($parentId, $projectId);

        if ($maxSort === null) {
            return self::DEFAULT_SORT_STEP;
        }

        return $maxSort + self::DEFAULT_SORT_STEP;
    }

    /**
     * Calculate sort value when inserting after a specified file.
     */
    private static function calculateAfterPositionSort(
        TaskFileRepositoryInterface $repository,
        ?int $parentId,
        int $preFileId,
        int $projectId
    ): int {
        $preSort = $repository->getSortByFileId($preFileId);
        if ($preSort === null) {
            // Preceding file does not exist; insert at the end
            return self::calculateLastPositionSort($repository, $parentId, $projectId);
        }

        $nextSort = $repository->getNextSortAfter($parentId, $preSort, $projectId);

        if ($nextSort === null) {
            // Insert at the end
            return $preSort + self::DEFAULT_SORT_STEP;
        }

        $gap = $nextSort - $preSort;
        if ($gap >= self::MIN_SORT_GAP) {
            return $preSort + intval($gap / 2);
        }

        // Gap is insufficient; trigger reordering then recalculate
        $updates = self::reorderSiblings($repository, $parentId, $projectId);
        if (! empty($updates)) {
            $repository->batchUpdateSort($updates);

            // After reordering, fetch position info again
            $preSort = $repository->getSortByFileId($preFileId);
            $nextSort = $repository->getNextSortAfter($parentId, $preSort, $projectId);

            if ($nextSort === null) {
                return $preSort + self::DEFAULT_SORT_STEP;
            }

            $newGap = $nextSort - $preSort;
            return $preSort + intval($newGap / 2);
        }

        // If reordering fails, fall back to inserting at the end
        return self::calculateLastPositionSort($repository, $parentId, $projectId);
    }
}
