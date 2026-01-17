<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;

interface TaskFileRepositoryInterface
{
    /**
     * Get file by ID.
     */
    public function getById(int $id): ?TaskFileEntity;

    /**
     * Batch get files by IDs.
     * @return TaskFileEntity[]
     */
    public function getFilesByIds(array $fileIds, int $projectId = 0): array;

    /**
     * Batch get files by IDs.
     * @return TaskFileEntity[]
     */
    public function getTaskFilesByIds(array $ids, int $projectId = 0): array;

    /**
     * Get file by fileKey.
     *
     * @param string $fileKey File key
     * @param null|int $topicId Topic ID, default is 0
     * @param bool $withTrash Whether to include deleted files, default is false
     */
    public function getByFileKey(string $fileKey, ?int $topicId = 0, bool $withTrash = false): ?TaskFileEntity;

    /**
     * Batch get files by fileKey array.
     *
     * @param array $fileKeys File key array
     * @return TaskFileEntity[] File entity array, indexed by file_key
     */
    public function getByFileKeys(array $fileKeys): array;

    /**
     * Get file by project ID and fileKey.
     */
    public function getByProjectIdAndFileKey(int $projectId, string $fileKey): ?TaskFileEntity;

    /**
     * Get file list by topic ID.
     *
     * @param int $topicId Topic ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param array $fileType File type filter
     * @param string $storageType Storage type
     * @return array{list: TaskFileEntity[], total: int} File list and total count
     */
    public function getByTopicId(int $topicId, int $page, int $pageSize, array $fileType = [], string $storageType = 'workspace'): array;

    /**
     * Get file list by project ID.
     *
     * @param int $projectId Project ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param array $fileType File type filter
     * @param string $storageType Storage type filter
     * @param null|string $updatedAfter Updated time filter (query files updated after this time)
     * @return array{list: TaskFileEntity[], total: int} File list and total count
     */
    public function getByProjectId(int $projectId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = '', ?string $updatedAfter = null): array;

    /**
     * Get file list by task ID.
     *
     * @param int $taskId Task ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array{list: TaskFileEntity[], total: int} File list and total count
     */
    public function getByTaskId(int $taskId, int $page, int $pageSize): array;

    /**
     * Get file list by topic task ID.
     *
     * @param int $topicTaskId Topic task ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array{list: TaskFileEntity[], total: int} File list and total count
     * @deprecated Use getByTopicId and getByTaskId methods instead
     */
    public function getByTopicTaskId(int $topicTaskId, int $page, int $pageSize): array;

    /**
     * Insert file.
     */
    public function insert(TaskFileEntity $entity): TaskFileEntity;

    /**
     * Insert file, ignore if conflict exists.
     * Determines conflict based on file_key and topic_id
     */
    public function insertOrIgnore(TaskFileEntity $entity): ?TaskFileEntity;

    /**
     * Insert or update file.
     * Uses INSERT ... ON DUPLICATE KEY UPDATE syntax
     * Updates existing record when file_key conflicts, otherwise inserts new record.
     *
     * @param TaskFileEntity $entity File entity
     * @return TaskFileEntity File entity after insert or update
     */
    public function insertOrUpdate(TaskFileEntity $entity): TaskFileEntity;

    /**
     * Update file.
     */
    public function updateById(TaskFileEntity $entity): TaskFileEntity;

    /**
     * Delete file by ID.
     *
     * @param int $id File ID
     * @param bool $forceDelete Whether to force delete (hard delete), default true
     */
    public function deleteById(int $id, bool $forceDelete = true): void;

    public function deleteByFileKeyAndProjectId(string $fileKey, int $projectId): int;

    /**
     * Batch get user files by file ID array and user ID.
     *
     * @param array $fileIds File ID array
     * @param string $userId User ID
     * @return TaskFileEntity[] User file list
     */
    public function findUserFilesByIds(array $fileIds, string $userId): array;

    public function findUserFilesByTopicId(string $topicId): array;

    public function findUserFilesByProjectId(string $projectId): array;

    /**
     * @return TaskFileEntity[] User file list
     */
    public function findFilesByProjectIdAndIds(int $projectId, array $fileIds): array;

    /**
     * Get all file_key list by project ID (high performance query).
     */
    public function getFileKeysByProjectId(int $projectId, int $limit = 1000): array;

    /**
     * Batch insert new file records.
     */
    public function batchInsertFiles(DataIsolation $dataIsolation, int $projectId, array $newFileKeys, array $objectStorageFiles = []): void;

    /**
     * Batch mark files as deleted.
     */
    public function batchMarkAsDeleted(array $deletedFileKeys): void;

    /**
     * Get minimum sort value under specified parent directory.
     */
    public function getMinSortByParentId(?int $parentId, int $projectId): ?int;

    /**
     * Get maximum sort value under specified parent directory.
     */
    public function getMaxSortByParentId(?int $parentId, int $projectId): ?int;

    /**
     * Get sort value of specified file.
     */
    public function getSortByFileId(int $fileId): ?int;

    /**
     * Get next sort value after specified sort value.
     */
    public function getNextSortAfter(?int $parentId, int $currentSort, int $projectId): ?int;

    /**
     * Get all sibling nodes under the same parent directory.
     */
    public function getSiblingsByParentId(?int $parentId, int $projectId, string $orderBy = 'sort', string $direction = 'ASC'): array;

    public function getSiblingCountByParentId(int $parentId, int $projectId): int;

    /**
     * Batch update sort values.
     */
    public function batchUpdateSort(array $updates): void;

    /**
     * Batch update file information.
     */
    public function batchUpdateFiles(array $updatedFileKeys): void;

    /**
     * Find file list by directory path.
     *
     * @param int $projectId Project ID
     * @param string $directoryPath Directory path
     * @param int $limit Query limit
     * @return TaskFileEntity[] File list
     */
    public function findFilesByDirectoryPath(int $projectId, string $directoryPath, int $limit = 1000): array;

    /**
     * Get children files by parent_id and project_id.
     *
     * @param int $projectId Project ID
     * @param int $parentId Parent directory ID
     * @param int $limit Maximum number of files to return
     * @return TaskFileEntity[] File entity list
     */
    public function getChildrenByParentAndProject(int $projectId, int $parentId, int $limit = 500): array;

    /**
     * Get children files by multiple parent_ids and project_id (batch query).
     * Uses idx_project_parent_sort index to avoid N+1 problem.
     *
     * @param int $projectId Project ID
     * @param array $parentIds Parent directory IDs
     * @param int $limit Maximum number of files to return
     * @return TaskFileEntity[] File entity list
     */
    public function getChildrenByParentIdsAndProject(int $projectId, array $parentIds, int $limit = 1000): array;

    /**
     * Batch update file_key for multiple files.
     *
     * @param array $updateBatch Array of [['file_id' => 1, 'file_key' => 'new/path', 'updated_at' => '...'], ...]
     * @return int Number of updated files
     */
    public function batchUpdateFileKeys(array $updateBatch): int;

    /**
     * Batch delete files.
     *
     * @param array $fileIds File ID array
     */
    public function deleteByIds(array $fileIds): void;

    /**
     * Batch delete files by file keys.
     *
     * @param array $fileKeys File key array
     */
    public function deleteByFileKeys(array $fileKeys): void;

    /**
     * Batch bind files to project with parent directory.
     * Updates both project_id and parent_id atomically.
     *
     * @param array $fileIds Array of file IDs to bind
     * @param int $projectId Project ID to bind to
     * @param int $parentId Parent directory ID
     * @return int Number of affected rows
     */
    public function batchBindToProject(array $fileIds, int $projectId, int $parentId): int;

    public function findLatestUpdatedByProjectId(int $projectId): ?TaskFileEntity;

    /**
     * Count files by project ID.
     *
     * @param int $projectId Project ID
     * @return int Total count of files in the project
     */
    public function countFilesByProjectId(int $projectId): int;

    /**
     * Get files by project ID with resume support.
     * Used for fork migration with pagination and resume capability.
     *
     * @param int $projectId Project ID
     * @param null|int $lastFileId Last processed file ID for resume
     * @param int $limit Number of files to fetch
     * @return TaskFileEntity[] Array of file entities
     */
    public function getFilesByProjectIdWithResume(int $projectId, ?int $lastFileId, int $limit): array;

    /**
     * Batch update parent_id for multiple files.
     * Used for fixing parent relationships during fork operations.
     *
     * @param array $fileIds Array of file IDs to update
     * @param int $parentId New parent ID to set
     * @param string $userId User performing the update
     * @return int Number of affected rows
     */
    public function batchUpdateParentId(array $fileIds, int $parentId, string $userId): int;

    public function updateFileByCondition(array $condition, array $data): bool;

    public function lockDirectChildrenForUpdate(int $parentId): array;

    public function getAllChildrenByParentId(int $parentId): array;

    /**
     * Restore deleted file.
     *
     * @param int $fileId File ID
     */
    public function restoreFile(int $fileId): void;
}
