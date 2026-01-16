<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;

interface TaskFileRepositoryInterface 
{
 /** * According toIDGetFile. */ 
    public function getById(int $id): ?TaskFileEntity; /** * According toIDBatchGetFile. * @return TaskFileEntity[] */ 
    public function getFilesByIds(array $fileIds, int $projectId = 0): array; /** * According toIDBatchGetFile. * @return TaskFileEntity[] */ 
    public function getTaskFilesByIds(array $ids, int $projectId = 0): array; /** * According tofileKeyGetFile. * * @param string $fileKey FileKey * @param null|int $topicId topic IDDefault to0 * @param bool $withTrash whether including delete dFileDefault tofalse */ 
    public function getByFileKey(string $fileKey, ?int $topicId = 0, bool $withTrash = false): ?TaskFileEntity; /** * According tofileKeyArrayBatchGetFile. * * @param array $fileKeys FileKeyArray * @return TaskFileEntity[] FileArrayfile_keyas Key */ 
    public function getByFileKeys(array $fileKeys): array; /** * According toProject IDfileKeyGetFile. */ 
    public function getByProjectIdAndFileKey(int $projectId, string $fileKey): ?TaskFileEntity; /** * According totopic IDGetFilelist . * * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @param array $fileType FileTypeFilter * @param string $storageType Type * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByTopicId(int $topicId, int $page, int $pageSize, array $fileType = [], string $storageType = 'workspace'): array; /** * According toProject IDGetFilelist . * * @param int $projectId Project ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @param array $fileType FileTypeFilter * @param string $storageType TypeFilter * @param null|string $updatedAfter Update timeFilterquery Timeafter UpdateFile * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByProjectId(int $projectId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = '', ?string $updatedAfter = null): array; /** * According toTaskIDGetFilelist . * * @param int $taskId TaskID * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByTaskId(int $taskId, int $page, int $pageSize): array; /** * According totopic TaskIDGetFilelist . * * @param int $topicTaskId topic TaskID * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total * @deprecated Using getByTopicId getByTaskId MethodSubstitute */ 
    public function getByTopicTaskId(int $topicTaskId, int $page, int $pageSize): array; /** * InsertFile. */ 
    public function insert(TaskFileEntity $entity): TaskFileEntity; /** * InsertFileIfExistConflictIgnore. * According tofile_keytopic_idDeterminewhether ExistConflict */ 
    public function insertOrIgnore(TaskFileEntity $entity): ?TaskFileEntity; /** * Insertor UpdateFile. * Using INSERT ... ON DUPLICATE KEY UPDATE * When file_key ConflictUpdateHaverecord OtherwiseInsertnew record . * * @param TaskFileEntity $entity File * @return TaskFileEntity Insertor UpdateFile */ 
    public function insertOrUpdate(TaskFileEntity $entity): TaskFileEntity; /** * UpdateFile. */ 
    public function updateById(TaskFileEntity $entity): TaskFileEntity; /** * delete file by ID. * * @param int $id File ID * @param bool $forcedelete whether to force delete (hard delete), default true */ 
    public function deleteById(int $id, bool $forcedelete = true): void; 
    public function deleteByFileKeyAndProjectId(string $fileKey, int $projectId): int; /** * According toFileIDArrayuser IDBatchGetuser File. * * @param array $fileIds FileIDArray * @param string $userId user ID * @return TaskFileEntity[] user Filelist */ 
    public function finduser FilesByIds(array $fileIds, string $userId): array; 
    public function finduser FilesByTopicId(string $topicId): array; 
    public function finduser FilesByProjectId(string $projectId): array; /** * @return TaskFileEntity[] user Filelist */ 
    public function findFilesByProjectIdAndIds(int $projectId, array $fileIds): array; /** * According toProject IDGetAllFilefile_keylist query . */ 
    public function getFileKeysByProjectId(int $projectId, int $limit = 1000): array; /** * BatchInsertNewFilerecord . */ 
    public function batchInsertFiles(DataIsolation $dataIsolation, int $projectId, array $newFileKeys, array $objectStorageFiles = []): void; /** * Batchmark Fileas delete d. */ 
    public function batchmark Asdelete d(array $deletedFileKeys): void; /** * Getspecified Directoryunder MinimumSortValue. */ 
    public function getMinSortByParentId(?int $parentId, int $projectId): ?int; /** * Getspecified Directoryunder MaximumSortValue. */ 
    public function getMaxSortByParentId(?int $parentId, int $projectId): ?int; /** * Getspecified FileSortValue. */ 
    public function getSortByFileId(int $fileId): ?int; /** * Getspecified SortValueafter NextSortValue. */ 
    public function getNextSortAfter(?int $parentId, int $currentSort, int $projectId): ?int; /** * GetDirectoryunder AllNode. */ 
    public function getSiblingsByParentId(?int $parentId, int $projectId, string $orderBy = 'sort', string $direction = 'ASC'): array; 
    public function getSiblingCountByParentId(int $parentId, int $projectId): int; /** * BatchUpdateSortValue. */ 
    public function batchUpdateSort(array $updates): void; /** * BatchUpdateFileinfo . */ 
    public function batchUpdateFiles(array $updatedFileKeys): void; /** * According toDirectoryPathFindFilelist . * * @param int $projectId Project ID * @param string $directoryPath DirectoryPath * @param int $limit query Limit * @return TaskFileEntity[] Filelist */ 
    public function findFilesByDirectoryPath(int $projectId, string $directoryPath, int $limit = 1000): array; /** * Get children files by parent_id and project_id. * * @param int $projectId Project ID * @param int $parentId Parent directory ID * @param int $limit Maximum number of files to return * @return TaskFileEntity[] File entity list */ 
    public function getChildrenByParentAndProject(int $projectId, int $parentId, int $limit = 500): array; /** * Get children files by multiple parent_ids and project_id (batch query). * Uses idx_project_parent_sort index to avoid N+1 problem. * * @param int $projectId Project ID * @param array $parentIds Parent directory IDs * @param int $limit Maximum number of files to return * @return TaskFileEntity[] File entity list */ 
    public function getChildrenByParentIdsAndProject(int $projectId, array $parentIds, int $limit = 1000): array; /** * Batch update file_key for multiple files. * * @param array $updateBatch Array of [['file_id' => 1, 'file_key' => 'new/path', 'updated_at' => '...'], ...] * @return int Number of updated files */ 
    public function batchUpdateFileKeys(array $updateBatch): int; /** * Batchdelete File. * * @param array $fileIds FileIDArray */ 
    public function deleteByIds(array $fileIds): void; /** * According toFileKeysBatchdelete File. * * @param array $fileKeys FileKeyArray */ 
    public function deleteByFileKeys(array $fileKeys): void; /** * Batch bind files to project with parent directory. * Updates both project_id and parent_id atomically. * * @param array $fileIds Array of file IDs to bind * @param int $projectId Project ID to bind to * @param int $parentId Parent directory ID * @return int Number of affected rows */ 
    public function batchBindToProject(array $fileIds, int $projectId, int $parentId): int; 
    public function findLatestUpdatedByProjectId(int $projectId): ?TaskFileEntity; /** * Count files by project ID. * * @param int $projectId Project ID * @return int Total count of files in the project */ 
    public function countFilesByProjectId(int $projectId): int; /** * Get files by project ID with resume support. * Used for fork migration with pagination and resume capability. * * @param int $projectId Project ID * @param null|int $lastFileId Last processed file ID for resume * @param int $limit Number of files to fetch * @return TaskFileEntity[] Array of file entities */ 
    public function getFilesByProjectIdWithResume(int $projectId, ?int $lastFileId, int $limit): array; /** * Batch update parent_id for multiple files. * Used for fixing parent relationships during fork operations. * * @param array $fileIds Array of file IDs to update * @param int $parentId New parent ID to set * @param string $userId user performing the update * @return int Number of affected rows */ 
    public function batchUpdateParentId(array $fileIds, int $parentId, string $userId): int; 
    public function updateFileByCondition(array $condition, array $data): bool; 
    public function lockDirectChildrenForUpdate(int $parentId): array; 
    public function getAllChildrenByParentId(int $parentId): array; /** * Resumedelete File. * * @param int $fileId FileID */ 
    public function restoreFile(int $fileId): void; 
}
 
