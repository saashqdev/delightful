<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskFileModel;
use Hyperf\DbConnection\Db;

class TaskFileRepository implements TaskFileRepositoryInterface
{
    public function __construct(protected TaskFileModel $model)
    {
    }

    public function getById(int $id): ?TaskFileEntity
    {
        $model = $this->model::query()->where('file_id', $id)->first();
        if (! $model) {
            return null;
        }
        return new TaskFileEntity($model->toArray());
    }

    public function getFilesByIds(array $fileIds, int $projectId = 0): array
    {
        $query = $this->model::query()->whereIn('file_id', $fileIds);

        if ($projectId > 0) {
            $query->where('project_id', $projectId);
        }

        $models = $query->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return $list;
    }

    /**
     * @return TaskFileEntity[]
     */
    public function getTaskFilesByIds(array $ids, int $projectId = 0): array
    {
        if (empty($ids)) {
            return [];
        }

        $query = $this->model::query()->whereIn('file_id', $ids);
        if ($projectId > 0) {
            $query = $query->where('project_id', $projectId);
        }
        $models = $query->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    /**
     * Get file by fileKey.
     *
     * @param string $fileKey File key
     * @param null|int $topicId Topic ID, default is 0
     * @param bool $withTrash Whether to include deleted files, default is false
     */
    public function getByFileKey(string $fileKey, ?int $topicId = 0, bool $withTrash = false): ?TaskFileEntity
    {
        // Determine query scope based on withTrash parameter
        if ($withTrash) {
            $query = $this->model::withTrashed();
        } else {
            $query = $this->model::query();
        }

        $query = $query->where('file_key', $fileKey);

        if ($topicId) {
            $query = $query->where('topic_id', $topicId);
        }

        $model = $query->first();

        if (! $model) {
            return null;
        }
        return new TaskFileEntity($model->toArray());
    }

    /**
     * Batch get files by fileKey array.
     */
    public function getByFileKeys(array $fileKeys): array
    {
        if (empty($fileKeys)) {
            return [];
        }

        $models = $this->model::query()
            ->whereIn('file_key', $fileKeys)
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entity = new TaskFileEntity($model->toArray());
            $entities[$entity->getFileKey()] = $entity;
        }

        return $entities;
    }

    /**
     * Get file by project ID and fileKey.
     */
    public function getByProjectIdAndFileKey(int $projectId, string $fileKey): ?TaskFileEntity
    {
        $model = $this->model::query()
            ->where('project_id', $projectId)
            ->where('file_key', $fileKey)
            ->first();

        if (! $model) {
            return null;
        }
        return new TaskFileEntity($model->toArray());
    }

    /**
     * Get file list by topic ID.
     *
     * @param int $topicId Topic ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param array $fileType File type filter
     * @param string $storageType Storage type
     * @return array{list: TaskFileEntity[], total: int} File list and total
     */
    public function getByTopicId(int $topicId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = 'workspace'): array
    {
        $offset = ($page - 1) * $pageSize;

        // Build query
        $query = $this->model::query()->where('topic_id', $topicId);

        // If file type array is specified and not empty, add file type filter condition
        if (! empty($fileType)) {
            $query->whereIn('file_type', $fileType);
        }

        // If storage type is specified, add storage type filter condition
        if (! empty($storageType)) {
            $query->where('storage_type', $storageType);
        }

        // Filter deleted files, deleted_at is not null
        $query->whereNull('deleted_at');

        // Get total first
        $total = $query->count();

        // Get paginated data, use Eloquent's get() method to apply $casts, sort by hierarchy and sort fields
        $models = $query->skip($offset)
            ->take($pageSize)
            ->orderBy('parent_id', 'ASC')  // Group by parent directory
            ->orderBy('sort', 'ASC')       // Sort by sort value
            ->orderBy('file_id', 'ASC')    // Sort by ID when sort values are the same
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    /**
     * Get file list by project ID.
     *
     * @param int $projectId Project ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param array $fileType File type filter
     * @param string $storageType Storage type filter
     * @param null|string $updatedAfter Update time filter (query files updated after this time)
     * @return array{list: TaskFileEntity[], total: int} File list and total
     */
    public function getByProjectId(int $projectId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = '', ?string $updatedAfter = null): array
    {
        $offset = ($page - 1) * $pageSize;

        // Build query
        $query = $this->model::query()->where('project_id', $projectId);

        // If file type array is specified and not empty, add file type filter condition
        if (! empty($fileType)) {
            $query->whereIn('file_type', $fileType);
        }

        // If storage type is specified, add storage type filter condition
        if (! empty($storageType)) {
            $query->where('storage_type', $storageType);
        }

        // If update time filter is specified, add time filter condition (database level filter)
        if ($updatedAfter !== null) {
            $query->where('updated_at', '>', $updatedAfter);
        }

        // Filter deleted files, deleted_at is not null
        $query->whereNull('deleted_at');

        // Get total first
        $total = $query->count();

        // Get paginated data, use Eloquent's get() method to make $casts effective, sort by hierarchy and sort field
        $models = $query->skip($offset)
            ->take($pageSize)
            ->orderBy('parent_id', 'ASC')  // Group by parent directory
            ->orderBy('sort', 'ASC')       // Sort by sort value
            ->orderBy('file_id', 'ASC')    // Sort by ID when sort values are the same
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    /**
     * Get file list by task ID.
     *
     * @param int $taskId Task ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array{list: TaskFileEntity[], total: int} File list and total
     */
    public function getByTaskId(int $taskId, int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;

        // Get total first
        $total = $this->model::query()
            ->where('task_id', $taskId)
            ->count();

        // Get paginated data, use Eloquent's get() method to make $casts effective
        $models = $this->model::query()
            ->where('task_id', $taskId)
            ->skip($offset)
            ->take($pageSize)
            ->orderBy('file_id', 'desc')
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    /**
     * Provided for backward compatibility.
     * @deprecated Use getByTopicId and getByTaskId instead
     */
    public function getByTopicTaskId(int $topicTaskId, int $page, int $pageSize): array
    {
        // Due to data structure changes, this method is no longer directly applicable
        // For backward compatibility, can try to find related data
        // Implement a simple empty result here
        return [
            'list' => [],
            'total' => 0,
        ];
    }

    public function insert(TaskFileEntity $entity): TaskFileEntity
    {
        $date = date('Y-m-d H:i:s');
        $entity->setCreatedAt($date);
        $entity->setUpdatedAt($date);

        $entityArray = $entity->toArray();
        $model = $this->model::query()->create($entityArray);

        // Set database-generated ID
        if (! empty($model->file_id)) {
            $entity->setFileId($entity->getFileId());
        }

        return $entity;
    }

    /**
     * Insert file, ignore if conflict exists.
     * Judge whether conflict exists based on file_key and topic_id
     */
    public function insertOrIgnore(TaskFileEntity $entity): ?TaskFileEntity
    {
        // First check if a record with the same file_key and topic_id already exists
        $existingEntity = $this->model::query()
            ->where('file_key', $entity->getFileKey())
            ->first();

        // If record already exists, return existing entity
        if ($existingEntity) {
            return new TaskFileEntity($existingEntity->toArray());
        }

        // Create new record if not exists
        $date = date('Y-m-d H:i:s');
        if (empty($entity->getFileId())) {
            $entity->setFileId(IdGenerator::getSnowId());
        }
        $entity->setCreatedAt($date);
        $entity->setUpdatedAt($date);

        $entityArray = $entity->toArray();

        $this->model::query()->create($entityArray);
        return $entity;
    }

    /**
     * Insert or update file.
     * Use INSERT ... ON DUPLICATE KEY UPDATE syntax
     * When file_key unique index conflicts, update existing record, otherwise insert new record.
     *
     * Mainly used to solve unique key conflict problems in high concurrency scenarios:
     * - Business layer queries and finds it doesn't exist
     * - But before insertion, other threads have already inserted the same file_key
     * - Use upsert at this time to avoid unique key conflict error
     */
    public function insertOrUpdate(TaskFileEntity $entity): TaskFileEntity
    {
        $date = date('Y-m-d H:i:s');

        // Prepare insert data
        if (empty($entity->getFileId())) {
            $entity->setFileId(IdGenerator::getSnowId());
        }
        if (empty($entity->getCreatedAt())) {
            $entity->setCreatedAt($date);
        }
        $entity->setUpdatedAt($date);

        $entityArray = $entity->toArray();

        // Use Hyperf's upsert method
        // Third parameter explicitly excludes file_id and created_at to ensure they won't be updated
        $affectedRows = $this->model::query()->upsert(
            [$entityArray],                    // Data
            ['file_key'],                      // Judge conflict based on file_key unique index
            array_values(array_diff(           // Explicitly specify fields to update
                array_keys($entityArray),
                ['file_id', 'file_key', 'created_at']  // Exclude primary key, unique key, created time
            ))
        );

        // Handle concurrency conflict situation
        // MySQL upsert's affected rows:
        //   1 = Inserted new record (normal situation)
        //   2 = Updated existing record (concurrency conflict)
        //   0 = All field values are the same, not actually updated (extremely rare)
        if ($affectedRows !== 1) {
            // Concurrency conflict occurred, need to get real file_id and created_at from database
            $record = $this->model::query()
                ->where('file_key', $entity->getFileKey())
                ->first();

            if ($record) {
                $entity->setFileId($record->file_id);
                $entity->setCreatedAt($record->created_at);
            }
        }

        return $entity;
    }

    public function updateById(TaskFileEntity $entity): TaskFileEntity
    {
        $entity->setUpdatedAt(date('Y-m-d H:i:s'));
        $entityArray = $entity->toArray();

        $this->model::query()
            ->where('file_id', $entity->getFileId())
            ->update($entityArray);

        return $entity;
    }

    public function updateFileByCondition(array $condition, array $data): bool
    {
        return $this->model::query()
            ->where($condition)
            ->update($data) > 0;
    }

    public function deleteById(int $id, bool $forceDelete = true): void
    {
        $query = $this->model::query()->where('file_id', $id);
        if ($forceDelete) {
            $query->forceDelete();
        } else {
            $query->delete();
        }
    }

    /**
     * Delete file by file_key and project_id (physical delete).
     */
    public function deleteByFileKeyAndProjectId(string $fileKey, int $projectId): int
    {
        $result = $this->model::query()->where('file_key', $fileKey)->where('project_id', $projectId)->forceDelete();
        return $result ?? 0;
    }

    public function getByFileKeyAndSandboxId(string $fileKey, int $sandboxId): ?TaskFileEntity
    {
        $model = $this->model::query()
            ->where('file_key', $fileKey)
            ->where('sandbox_id', $sandboxId)
            ->first();
        if (! $model) {
            return null;
        }
        return new TaskFileEntity($model->toArray());
    }

    /**
     * Batch get user files by file ID array and user ID.
     *
     * @param array $fileIds File ID array
     * @param string $userId User ID
     * @return TaskFileEntity[] User file list
     */
    public function findUserFilesByIds(array $fileIds, string $userId): array
    {
        if (empty($fileIds)) {
            return [];
        }

        // Query files belonging to specified user
        $models = $this->model::query()
            ->whereIn('file_id', $fileIds)
            ->where('user_id', $userId)
            ->whereNull('deleted_at') // Filter deleted files
            ->orderBy('file_id', 'desc')
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    public function findUserFilesByTopicId(string $topicId): array
    {
        $models = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('is_hidden', 0)
            ->whereNull('deleted_at') // Filter deleted files
            ->orderBy('file_id', 'desc')
            ->limit(1000)
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    public function findUserFilesByProjectId(string $projectId): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->where('is_hidden', 0)
            ->whereNull('deleted_at') // Filter deleted files
            ->orderBy('file_id', 'desc')
            ->limit(1000)
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    /**
     * @return array|TaskFileEntity[]
     */
    public function findFilesByProjectIdAndIds(int $projectId, array $fileIds): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->whereIn('file_id', $fileIds)
            ->whereNull('deleted_at') // Filter deleted files
            ->orderBy('file_id', 'desc')
            ->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    /**
     * Get all file_key list by project ID (high performance query).
     */
    public function getFileKeysByProjectId(int $projectId, int $limit = 1000): array
    {
        $query = $this->model::query()
            ->select(['file_key'])
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->limit($limit);

        return $query->pluck('file_key')->toArray();
    }

    /**
     * Batch insert new file records.
     */
    public function batchInsertFiles(DataIsolation $dataIsolation, int $projectId, array $newFileKeys, array $objectStorageFiles = []): void
    {
        if (empty($newFileKeys)) {
            return;
        }

        $insertData = [];
        $now = date('Y-m-d H:i:s');

        foreach ($newFileKeys as $fileKey) {
            // Get detailed information from object storage file information
            $fileInfo = $objectStorageFiles[$fileKey] ?? [];

            $insertData[] = [
                'file_id' => IdGenerator::getSnowId(),
                'user_id' => $dataIsolation->getCurrentUserId(),
                'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
                'project_id' => $projectId,
                'topic_id' => 0,
                'task_id' => 0,
                'file_key' => $fileKey,
                'file_name' => $fileInfo['file_name'] ?? basename($fileKey),
                'file_extension' => $fileInfo['file_extension'] ?? pathinfo($fileKey, PATHINFO_EXTENSION),
                'file_type' => 'auto_sync',
                'file_size' => $fileInfo['file_size'] ?? 0,
                'storage_type' => 'workspace',
                'is_hidden' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Use batch insert to improve performance
        $this->model::query()->insert($insertData);
    }

    /**
     * Batch mark files as deleted.
     */
    public function batchMarkAsDeleted(array $deletedFileKeys): void
    {
        if (empty($deletedFileKeys)) {
            return;
        }

        $this->model::query()
            ->whereIn('file_key', $deletedFileKeys)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Batch update file information.
     */
    public function batchUpdateFiles(array $updatedFileKeys): void
    {
        if (empty($updatedFileKeys)) {
            return;
        }

        // Simplified implementation: only update modification time
        $this->model::query()
            ->whereIn('file_key', $updatedFileKeys)
            ->whereNull('deleted_at')
            ->update([
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Find file list by directory path.
     */
    public function findFilesByDirectoryPath(int $projectId, string $directoryPath, int $limit = 1000): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->where('file_key', 'like', $directoryPath . '%')
            ->whereNull('deleted_at')
            ->limit($limit)
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return $list;
    }

    /**
     * Find child file list by parent_id and project_id.
     * This query will use index: idx_project_parent_sort (project_id, parent_id, sort, file_id).
     */
    public function getChildrenByParentAndProject(int $projectId, int $parentId, int $limit = 500): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at')
            ->limit($limit)
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return $list;
    }

    /**
     * Batch query child files of multiple parent directories (use IN query to avoid N+1 problem).
     * Use idx_project_parent_sort index.
     *
     * @param int $projectId Project ID
     * @param array $parentIds Parent directory ID array
     * @param int $limit Limit count
     * @return TaskFileEntity[] File entity list
     */
    public function getChildrenByParentIdsAndProject(int $projectId, array $parentIds, int $limit = 1000): array
    {
        if (empty($parentIds)) {
            return [];
        }

        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->whereIn('parent_id', $parentIds)
            ->whereNull('deleted_at')
            ->limit($limit)
            ->get();

        $list = [];
        foreach ($models as $model) {
            $list[] = new TaskFileEntity($model->toArray());
        }

        return $list;
    }

    /**
     * Batch update file_key of files.
     * Use CASE WHEN statement to implement one-time batch update.
     *
     * @param array $updateBatch [['file_id' => 1, 'file_key' => 'new/path', 'updated_at' => '...'], ...]
     * @return int Number of files updated
     */
    public function batchUpdateFileKeys(array $updateBatch): int
    {
        if (empty($updateBatch)) {
            return 0;
        }

        $fileIds = array_column($updateBatch, 'file_id');

        // Build CASE WHEN statement and binding parameters (correct order)
        $fileKeyCases = [];
        $updatedAtCases = [];
        $fileKeyBindings = [];
        $updatedAtBindings = [];

        foreach ($updateBatch as $item) {
            $fileKeyCases[] = 'WHEN ? THEN ?';
            $updatedAtCases[] = 'WHEN ? THEN ?';

            // file_key parameters
            $fileKeyBindings[] = $item['file_id'];
            $fileKeyBindings[] = $item['file_key'];

            // updated_at parameters
            $updatedAtBindings[] = $item['file_id'];
            $updatedAtBindings[] = $item['updated_at'];
        }

        $fileKeyCasesSql = implode(' ', $fileKeyCases);
        $updatedAtCasesSql = implode(' ', $updatedAtCases);

        // Build SQL (merge parameters in correct order)
        $sql = sprintf(
            'UPDATE %s SET 
                file_key = CASE file_id %s END,
                updated_at = CASE file_id %s END
                WHERE file_id IN (%s)',
            $this->model->getTable(),
            $fileKeyCasesSql,
            $updatedAtCasesSql,
            implode(',', array_fill(0, count($fileIds), '?'))
        );

        // Correct parameter order: file_key CASE first, then updated_at CASE, finally WHERE IN
        $bindings = array_merge($fileKeyBindings, $updatedAtBindings, $fileIds);

        return Db::update($sql, $bindings);
    }

    /**
     * Batch delete files (physical deletion).
     */
    public function deleteByIds(array $fileIds): void
    {
        if (empty($fileIds)) {
            return;
        }

        $this->model::query()
            ->whereIn('file_id', $fileIds)
            ->forceDelete();
    }

    /**
     * Batch delete files by file keys (physical deletion).
     */
    public function deleteByFileKeys(array $fileKeys): void
    {
        if (empty($fileKeys)) {
            return;
        }

        $this->model::query()
            ->whereIn('file_key', $fileKeys)
            ->forceDelete();
    }

    /**
     * Get the minimum sort value under the specified parent directory.
     */
    public function getMinSortByParentId(?int $parentId, int $projectId): ?int
    {
        $query = $this->model::query()
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->min('sort');
    }

    /**
     * Get the maximum sort value under the specified parent directory.
     */
    public function getMaxSortByParentId(?int $parentId, int $projectId): ?int
    {
        $query = $this->model::query()
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->max('sort');
    }

    /**
     * Get the sort value of the specified file.
     */
    public function getSortByFileId(int $fileId): ?int
    {
        return $this->model::query()
            ->where('file_id', $fileId)
            ->whereNull('deleted_at')
            ->value('sort');
    }

    /**
     * Get the next sort value after the specified sort value.
     */
    public function getNextSortAfter(?int $parentId, int $currentSort, int $projectId): ?int
    {
        $query = $this->model::query()
            ->where('project_id', $projectId)
            ->where('sort', '>', $currentSort)
            ->whereNull('deleted_at');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->min('sort');
    }

    /**
     * Get all sibling nodes under the same parent directory.
     */
    public function getSiblingsByParentId(?int $parentId, int $projectId, string $orderBy = 'sort', string $direction = 'ASC'): array
    {
        $query = $this->model::query()
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->orderBy($orderBy, $direction)->get()->toArray();
    }

    public function getSiblingCountByParentId(int $parentId, int $projectId): int
    {
        return $this->model::query()
            ->where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Batch update sort values.
     */
    public function batchUpdateSort(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        foreach ($updates as $update) {
            $this->model::query()
                ->where('file_id', $update['file_id'])
                ->update(['sort' => $update['sort'], 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Batch bind files to project with parent directory.
     * Updates both project_id and parent_id atomically.
     */
    public function batchBindToProject(array $fileIds, int $projectId, int $parentId): int
    {
        if (empty($fileIds)) {
            return 0;
        }

        return $this->model::query()
            ->whereIn('file_id', $fileIds)
            ->where(function ($query) {
                $query->whereNull('project_id')
                    ->orWhere('project_id', 0);
            })
            ->update([
                'project_id' => $projectId,
                'parent_id' => $parentId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function findLatestUpdatedByProjectId(int $projectId): ?TaskFileEntity
    {
        $model = $this->model::query()
            ->withTrashed()
            ->where('project_id', $projectId)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (! $model) {
            return null;
        }

        return new TaskFileEntity($model->toArray());
    }

    /**
     * Count files by project ID.
     */
    public function countFilesByProjectId(int $projectId): int
    {
        return $this->model::query()
            ->where('project_id', $projectId)
            ->where('storage_type', StorageType::WORKSPACE->value)
            ->where('is_hidden', false)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get files by project ID with resume support.
     * Used for fork migration with pagination and resume capability.
     */
    public function getFilesByProjectIdWithResume(int $projectId, ?int $lastFileId, int $limit): array
    {
        $query = $this->model::query()
            ->where('project_id', $projectId)
            ->where('storage_type', StorageType::WORKSPACE->value)
            ->where('is_hidden', false)
            ->whereNull('deleted_at')
            ->orderBy('file_id', 'asc')
            ->limit($limit);

        // Support resume from last file ID
        if ($lastFileId !== null) {
            $query->where('file_id', '>', $lastFileId);
        }

        $models = $query->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = new TaskFileEntity($model->toArray());
        }

        return $entities;
    }

    /**
     * Batch update parent_id for multiple files.
     * Used for fixing parent relationships during fork operations.
     */
    public function batchUpdateParentId(array $fileIds, int $parentId, string $userId): int
    {
        if (empty($fileIds)) {
            return 0;
        }

        return $this->model::query()
            ->whereIn('file_id', $fileIds)
            ->whereNull('deleted_at')
            ->update([
                'parent_id' => $parentId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function lockDirectChildrenForUpdate(int $parentId): array
    {
        return $this->model::query()
            ->where('parent_id', $parentId)
            ->orderBy('sort', 'ASC')
            ->orderBy('file_id', 'ASC')
            ->lockForUpdate()
            ->get()
            ->toArray();
    }

    public function getAllChildrenByParentId(int $parentId): array
    {
        return $this->model::query()
            ->where('parent_id', $parentId)
            ->orderBy('sort', 'ASC')
            ->orderBy('file_id', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * Restore a deleted file.
     */
    public function restoreFile(int $fileId): void
    {
        $this->model::withTrashed()
            ->where('file_id', $fileId)
            ->restore();
    }
}
