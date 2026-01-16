<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileRepositoryInterface;
/** * FileSorttool Class * Responsible for file sort algorithm implementation, following base infrastructure layer responsibilities. */

class FileSortUtil 
{
 
    public 
    const DEFAULT_SORT_STEP = 10000; 
    public 
    const MIN_SORT_GAP = 10; 
    public 
    const MAX_SORT_VALUE = 9223372036854775807; // bigint MaximumValue /** * Calculate sort value for new file * * @param TaskFileRepositoryInterface $repository Repository

interface * @param null|int $parentId Parent directory ID * @param int $preFileId File ID before which to insert, 0 means insert at first position, -1 means insert at end * @param int $projectId Project IDfor Data * @return int Calculate d sort value */ 
    public 
    static function calculateSortValue( TaskFileRepositoryInterface $repository, ?int $parentId, int $preFileId, int $projectId ): int 
{
 if ($preFileId === 0) 
{
 // Insert at first position return self::calculateFirstPositionSort($repository, $parentId, $projectId); 
}
 if ($preFileId === -1) 
{
 // Insert at endDefaultbehavior is  return self::calculateLastPositionSort($repository, $parentId, $projectId); 
}
 // Insert after specified file return self::calculateAfterPositionSort($repository, $parentId, $preFileId, $projectId); 
}
 /** * Batch re-sort files at same level. * * @param TaskFileRepositoryInterface $repository Repository

interface * @param null|int $parentId Parent directory ID * @param int $projectId Project ID * @return array Sort data that needs to be updated */ 
    public 
    static function reorderSiblings( TaskFileRepositoryInterface $repository, ?int $parentId, int $projectId ): array 
{
 $siblings = $repository->getSiblingsByParentId($parentId, $projectId, 'sort', 'ASC'); $updates = []; foreach ($siblings as $index => $sibling) 
{
 $newSort = ($index + 1) * self::DEFAULT_SORT_STEP; if ($sibling['sort'] !== $newSort) 
{
 $updates[] = [ 'file_id' => $sibling['file_id'], 'sort' => $newSort, ]; 
}
 
}
 return $updates; 
}
 /** * Get default sort value (for new files without specified position). */ 
    public 
    static function getDefaultSortValue( TaskFileRepositoryInterface $repository, ?int $parentId, int $projectId ): int 
{
 return self::calculateLastPositionSort($repository, $parentId, $projectId); 
}
 /** * Validate sort parameter validity. */ 
    public 
    static function validateSortParams(?int $parentId, int $preFileId, int $projectId): bool 
{
 // When preFileId is 0 or -1, no validation needed if ($preFileId <= 0) 
{
 return true; 
}
 // TODO: Can add more validation logicValidate if preFileId exists and belongs to same parent directory return true; 
}
 /** * Calculate sort value for inserting at first position */ 
    private 
    static function calculateFirstPositionSort( TaskFileRepositoryInterface $repository, ?int $parentId, int $projectId ): int 
{
 $minSort = $repository->getMinSortByParentId($parentId, $projectId); if ($minSort === null) 
{
 // Don't haveFileUsingDefault value return self::DEFAULT_SORT_STEP; 
}
 if ($minSort > self::DEFAULT_SORT_STEP) 
{
 // Minimum value is large, can use default value to insert at front return self::DEFAULT_SORT_STEP;

}
 // Try to calculate a smaller value, using half the step $halfStep = intval(self::DEFAULT_SORT_STEP / 2); if ($minSort > $halfStep) 
{
 return $minSort - $halfStep; 
}
 // If minimum value is too small to insert reasonable value, need to re-sort // Should trigger re-sort logic here, temporarily return default value return self::DEFAULT_SORT_STEP; 
}
 /** * Calculate sort value for inserting at end */ 
    private 
    static function calculateLastPositionSort( TaskFileRepositoryInterface $repository, ?int $parentId, int $projectId ): int 
{
 $maxSort = $repository->getMaxSortByParentId($parentId, $projectId); if ($maxSort === null) 
{
 return self::DEFAULT_SORT_STEP; 
}
 return $maxSort + self::DEFAULT_SORT_STEP; 
}
 /** * Calculate sort value for inserting after specified file */ 
    private 
    static function calculateAfterPositionSort( TaskFileRepositoryInterface $repository, ?int $parentId, int $preFileId, int $projectId ): int 
{
 $preSort = $repository->getSortByFileId($preFileId); if ($preSort === null) 
{
 // PrependFiledoes not existInsert at end return self::calculateLastPositionSort($repository, $parentId, $projectId); 
}
 $nextSort = $repository->getNextSortAfter($parentId, $preSort, $projectId); if ($nextSort === null) 
{
 // Insert at end return $preSort + self::DEFAULT_SORT_STEP; 
}
 $gap = $nextSort - $preSort; if ($gap >= self::MIN_SORT_GAP) 
{
 return $preSort + intval($gap / 2); 
}
 // Not enough gap, trigger re-sort then recalculate $updates = self::reorderSiblings($repository, $parentId, $projectId); if (! empty($updates)) 
{
 $repository->batchUpdateSort($updates); // Re-get position info after re-sorting $preSort = $repository->getSortByFileId($preFileId); $nextSort = $repository->getNextSortAfter($parentId, $preSort, $projectId); if ($nextSort === null) 
{
 return $preSort + self::DEFAULT_SORT_STEP; 
}
 $newGap = $nextSort - $preSort; return $preSort + intval($newGap / 2); 
}
 // If re-sort fails, fall back to insert at end return self::calculateLastPositionSort($repository, $parentId, $projectId); 
}
 
}
 
