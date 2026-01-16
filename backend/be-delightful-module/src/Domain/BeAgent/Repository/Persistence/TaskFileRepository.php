<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskFileModel;
use Hyperf\DbConnection\Db;

class TaskFileRepository implements TaskFileRepositoryInterface 
{
 
    public function __construct(
    protected TaskFileModel $model) 
{
 
}
 
    public function getById(int $id): ?TaskFileEntity 
{
 $model = $this->model::query()->where('file_id', $id)->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileEntity($model->toArray()); 
}
 
    public function getFilesByIds(array $fileIds, int $projectId = 0): array 
{
 $query = $this->model::query()->whereIn('file_id', $fileIds); if ($projectId > 0) 
{
 $query->where('project_id', $projectId); 
}
 $models = $query->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return $list; 
}
 /** * @return TaskFileEntity[] */ 
    public function getTaskFilesByIds(array $ids, int $projectId = 0): array 
{
 if (empty($ids)) 
{
 return []; 
}
 $query = $this->model::query()->whereIn('file_id', $ids); if ($projectId > 0) 
{
 $query = $query->where('project_id', $projectId); 
}
 $models = $query->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 /** * According tofileKeyGetFile. * * @param string $fileKey FileKey * @param null|int $topicId topic IDDefault to0 * @param bool $withTrash whether including delete dFileDefault tofalse */ 
    public function getByFileKey(string $fileKey, ?int $topicId = 0, bool $withTrash = false): ?TaskFileEntity 
{
 // According towithTrashparameter decide query Range if ($withTrash) 
{
 $query = $this->model::withTrashed(); 
}
 else 
{
 $query = $this->model::query(); 
}
 $query = $query->where('file_key', $fileKey); if ($topicId) 
{
 $query = $query->where('topic_id', $topicId); 
}
 $model = $query->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileEntity($model->toArray()); 
}
 /** * According tofileKeyArrayBatchGetFile. */ 
    public function getByFileKeys(array $fileKeys): array 
{
 if (empty($fileKeys)) 
{
 return []; 
}
 $models = $this->model::query() ->whereIn('file_key', $fileKeys) ->get(); $entities = []; foreach ($models as $model) 
{
 $entity = new TaskFileEntity($model->toArray()); $entities[$entity->getFileKey()] = $entity; 
}
 return $entities; 
}
 /** * According toProject IDfileKeyGetFile. */ 
    public function getByProjectIdAndFileKey(int $projectId, string $fileKey): ?TaskFileEntity 
{
 $model = $this->model::query() ->where('project_id', $projectId) ->where('file_key', $fileKey) ->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileEntity($model->toArray()); 
}
 /** * According totopic IDGetFilelist . * * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @param array $fileType FileTypeFilter * @param string $storageType Type * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByTopicId(int $topicId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = 'workspace'): array 
{
 $offset = ($page - 1) * $pageSize; // Buildquery $query = $this->model::query()->where('topic_id', $topicId); // Ifspecified ed FileTypeArrayand EmptyAddFileTypeFilterCondition if (! empty($fileType)) 
{
 $query->whereIn('file_type', $fileType); 
}
 // Ifspecified ed TypeAddTypeFilterCondition if (! empty($storageType)) 
{
 $query->where('storage_type', $storageType); 
}
 // Filteralready delete  deleted_at Empty $query->whereNull('deleted_at'); // GetTotal $total = $query->count(); // GetPagingDataUsingEloquentget()Method$castsZ-indexSortFieldSort $models = $query->skip($offset) ->take($pageSize) ->orderBy('parent_id', 'ASC') // DirectoryGroup ->orderBy('sort', 'ASC') // Sort by sort value ->orderBy('file_id', 'ASC') // SortValueMeanwhileIDSort ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return [ 'list' => $list, 'total' => $total, ]; 
}
 /** * According toProject IDGetFilelist . * * @param int $projectId Project ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @param array $fileType FileTypeFilter * @param string $storageType TypeFilter * @param null|string $updatedAfter Update timeFilterquery Timeafter UpdateFile * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByProjectId(int $projectId, int $page, int $pageSize = 200, array $fileType = [], string $storageType = '', ?string $updatedAfter = null): array 
{
 $offset = ($page - 1) * $pageSize; // Buildquery $query = $this->model::query()->where('project_id', $projectId); // Ifspecified ed FileTypeArrayand EmptyAddFileTypeFilterCondition if (! empty($fileType)) 
{
 $query->whereIn('file_type', $fileType); 
}
 // Ifspecified ed TypeAddTypeFilterCondition if (! empty($storageType)) 
{
 $query->where('storage_type', $storageType); 
}
 // Ifspecified ed Update timeFilterAddTimeFilterConditionDatabaseLevelFilter if ($updatedAfter !== null) 
{
 $query->where('updated_at', '>', $updatedAfter); 
}
 // Filteralready delete  deleted_at Empty $query->whereNull('deleted_at'); // GetTotal $total = $query->count(); // GetPagingDataUsingEloquentget()Method$castsZ-indexSortFieldSort $models = $query->skip($offset) ->take($pageSize) ->orderBy('parent_id', 'ASC') // DirectoryGroup ->orderBy('sort', 'ASC') // Sort by sort value ->orderBy('file_id', 'ASC') // SortValueMeanwhileIDSort ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return [ 'list' => $list, 'total' => $total, ]; 
}
 /** * According toTaskIDGetFilelist . * * @param int $taskId TaskID * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array
{
list: TaskFileEntity[], total: int
}
 Filelist Total */ 
    public function getByTaskId(int $taskId, int $page, int $pageSize): array 
{
 $offset = ($page - 1) * $pageSize; // GetTotal $total = $this->model::query() ->where('task_id', $taskId) ->count(); // GetPagingDataUsingEloquentget()Method$casts $models = $this->model::query() ->where('task_id', $taskId) ->skip($offset) ->take($pageSize) ->orderBy('file_id', 'desc') ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return [ 'list' => $list, 'total' => $total, ]; 
}
 /** * as CompatibleMethod. * @deprecated Using getByTopicId getByTaskId */ 
    public function getByTopicTaskId(int $topicTaskId, int $page, int $pageSize): array 
{
 // DataStructureMethodNo longer directly applicable // as CompatibleCantry Findrelated Data // ImplementationSimpleEmptyResult return [ 'list' => [], 'total' => 0, ]; 
}
 
    public function insert(TaskFileEntity $entity): TaskFileEntity 
{
 $date = date('Y-m-d H:i:s'); $entity->setCreatedAt($date); $entity->setUpdatedAt($date); $entityArray = $entity->toArray(); $model = $this->model::query()->create($entityArray); // Set DatabaseGenerate ID if (! empty($model->file_id)) 
{
 $entity->setFileId($entity->getFileId()); 
}
 return $entity; 
}
 /** * InsertFileIfExistConflictIgnore. * According tofile_keytopic_idDeterminewhether ExistConflict */ 
    public function insertOrIgnore(TaskFileEntity $entity): ?TaskFileEntity 
{
 // Firstcheck whether already ExistSamefile_keytopic_idrecord $existingEntity = $this->model::query() ->where('file_key', $entity->getFileKey()) ->first(); // IfAlready existsrecord Return Already exists if ($existingEntity) 
{
 return new TaskFileEntity($existingEntity->toArray()); 
}
 // does not existCreatenew record $date = date('Y-m-d H:i:s'); if (empty($entity->getFileId())) 
{
 $entity->setFileId(IdGenerator::getSnowId()); 
}
 $entity->setCreatedAt($date); $entity->setUpdatedAt($date); $entityArray = $entity->toArray(); $this->model::query()->create($entityArray); return $entity; 
}
 /** * Insertor UpdateFile. * Using INSERT ... ON DUPLICATE KEY UPDATE * When file_key IndexConflictUpdateHaverecord OtherwiseInsertnew record . * * Primaryfor Concurrencyunder KeyConflict * - query does not exist * - AtInsertThreadalready Inserted Same file_key * - Using upsert KeyConflict */ 
    public function insertOrUpdate(TaskFileEntity $entity): TaskFileEntity 
{
 $date = date('Y-m-d H:i:s'); // InsertData if (empty($entity->getFileId())) 
{
 $entity->setFileId(IdGenerator::getSnowId()); 
}
 if (empty($entity->getCreatedAt())) 
{
 $entity->setCreatedAt($date); 
}
 $entity->setUpdatedAt($date); $entityArray = $entity->toArray(); // Using Hyperf upsert Method // ThirdParameterClearExclude file_id created_atEnsureUpdate $affectedRows = $this->model::query()->upsert( [$entityArray], // Data ['file_key'], // Based on file_key IndexDetermineConflict array_values(array_diff( // Clearspecified UpdateField array_keys($entityArray), ['file_id', 'file_key', 'created_at'] // Excludeprimary key KeyCreation time )) ); // process ConcurrencyConflict // MySQL upsert affected rows // 1 = Inserted new record (normal case) // 2 = Updateed Already existsrecord ConcurrencyConflict // 0 = All field values same, no actual update (very rare) if ($affectedRows !== 1) 
{
 // occurred ed ConcurrencyConflictneed GetDatabasein Real file_id created_at $record = $this->model::query() ->where('file_key', $entity->getFileKey()) ->first(); if ($record) 
{
 $entity->setFileId($record->file_id); $entity->setCreatedAt($record->created_at); 
}
 
}
 return $entity; 
}
 
    public function updateById(TaskFileEntity $entity): TaskFileEntity 
{
 $entity->setUpdatedAt(date('Y-m-d H:i:s')); $entityArray = $entity->toArray(); $this->model::query() ->where('file_id', $entity->getFileId()) ->update($entityArray); return $entity; 
}
 
    public function updateFileByCondition(array $condition, array $data): bool 
{
 return $this->model::query() ->where($condition) ->update($data) > 0; 
}
 
    public function deleteById(int $id, bool $forcedelete = true): void 
{
 $query = $this->model::query()->where('file_id', $id); if ($forcedelete ) 
{
 $query->forcedelete (); 
}
 else 
{
 $query->delete(); 
}
 
}
 /** * According to file_key project_id delete Filedelete . */ 
    public function deleteByFileKeyAndProjectId(string $fileKey, int $projectId): int 
{
 $result = $this->model::query()->where('file_key', $fileKey)->where('project_id', $projectId)->forcedelete (); return $result ?? 0; 
}
 
    public function getByFileKeyAndSandboxId(string $fileKey, int $sandboxId): ?TaskFileEntity 
{
 $model = $this->model::query() ->where('file_key', $fileKey) ->where('sandbox_id', $sandboxId) ->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileEntity($model->toArray()); 
}
 /** * According toFileIDArrayuser IDBatchGetuser File. * * @param array $fileIds FileIDArray * @param string $userId user ID * @return TaskFileEntity[] user Filelist */ 
    public function finduser FilesByIds(array $fileIds, string $userId): array 
{
 if (empty($fileIds)) 
{
 return []; 
}
 // query belongs to specified user File $models = $this->model::query() ->whereIn('file_id', $fileIds) ->where('user_id', $userId) ->whereNull('deleted_at') // Filterdelete dFile ->orderBy('file_id', 'desc') ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 
    public function finduser FilesByTopicId(string $topicId): array 
{
 $models = $this->model::query() ->where('topic_id', $topicId) ->where('is_hidden', 0) ->whereNull('deleted_at') // Filterdelete dFile ->orderBy('file_id', 'desc') ->limit(1000) ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 
    public function finduser FilesByProjectId(string $projectId): array 
{
 $models = $this->model::query() ->where('project_id', $projectId) ->where('is_hidden', 0) ->whereNull('deleted_at') // Filterdelete dFile ->orderBy('file_id', 'desc') ->limit(1000) ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 /** * @return array|TaskFileEntity[] */ 
    public function findFilesByProjectIdAndIds(int $projectId, array $fileIds): array 
{
 $models = $this->model::query() ->where('project_id', $projectId) ->whereIn('file_id', $fileIds) ->whereNull('deleted_at') // Filterdelete dFile ->orderBy('file_id', 'desc') ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 /** * According toProject IDGetAllFilefile_keylist query . */ 
    public function getFileKeysByProjectId(int $projectId, int $limit = 1000): array 
{
 $query = $this->model::query() ->select(['file_key']) ->where('project_id', $projectId) ->whereNull('deleted_at') ->limit($limit); return $query->pluck('file_key')->toArray(); 
}
 /** * BatchInsertNewFilerecord . */ 
    public function batchInsertFiles(DataIsolation $dataIsolation, int $projectId, array $newFileKeys, array $objectStorageFiles = []): void 
{
 if (empty($newFileKeys)) 
{
 return; 
}
 $insertData = []; $now = date('Y-m-d H:i:s'); foreach ($newFileKeys as $fileKey) 
{
 // FromObjectFileinfo in Getinfo $fileinfo = $objectStorageFiles[$fileKey] ?? []; $insertData[] = [ 'file_id' => IdGenerator::getSnowId(), 'user_id' => $dataIsolation->getcurrent user Id(), 'organization_code' => $dataIsolation->getcurrent OrganizationCode(), 'project_id' => $projectId, 'topic_id' => 0, 'task_id' => 0, 'file_key' => $fileKey, 'file_name' => $fileinfo ['file_name'] ?? basename($fileKey), 'file_extension' => $fileinfo ['file_extension'] ?? pathinfo($fileKey, PATHINFO_EXTENSION), 'file_type' => 'auto_sync', 'file_size' => $fileinfo ['file_size'] ?? 0, 'storage_type' => 'workspace', 'is_hidden' => false, 'created_at' => $now, 'updated_at' => $now, ]; 
}
 // UsingBatchInsert $this->model::query()->insert($insertData); 
}
 /** * Batchmark Fileas delete d. */ 
    public function batchmark Asdelete d(array $deletedFileKeys): void 
{
 if (empty($deletedFileKeys)) 
{
 return; 
}
 $this->model::query() ->whereIn('file_key', $deletedFileKeys) ->whereNull('deleted_at') ->update([ 'deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), ]); 
}
 /** * BatchUpdateFileinfo . */ 
    public function batchUpdateFiles(array $updatedFileKeys): void 
{
 if (empty($updatedFileKeys)) 
{
 return; 
}
 // ImplementationUpdateModifyTime $this->model::query() ->whereIn('file_key', $updatedFileKeys) ->whereNull('deleted_at') ->update([ 'updated_at' => date('Y-m-d H:i:s'), ]); 
}
 /** * According toDirectoryPathFindFilelist . */ 
    public function findFilesByDirectoryPath(int $projectId, string $directoryPath, int $limit = 1000): array 
{
 $models = $this->model::query() ->where('project_id', $projectId) ->where('file_key', 'like', $directoryPath . '%') ->whereNull('deleted_at') ->limit($limit) ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return $list; 
}
 /** * According to parent_id project_id FindFilelist . * query UsingIndex: idx_project_parent_sort (project_id, parent_id, sort, file_id). */ 
    public function getChildrenByParentAndProject(int $projectId, int $parentId, int $limit = 500): array 
{
 $models = $this->model::query() ->where('project_id', $projectId) ->where('parent_id', $parentId) ->whereNull('deleted_at') ->limit($limit) ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return $list; 
}
 /** * Batchquery MultipleDirectoryFileUsing IN query  N+1 . * Using idx_project_parent_sort Index. * * @param int $projectId Project ID * @param array $parentIds Parent directory IDArray * @param int $limit LimitQuantity * @return TaskFileEntity[] Filelist */ 
    public function getChildrenByParentIdsAndProject(int $projectId, array $parentIds, int $limit = 1000): array 
{
 if (empty($parentIds)) 
{
 return []; 
}
 $models = $this->model::query() ->where('project_id', $projectId) ->whereIn('parent_id', $parentIds) ->whereNull('deleted_at') ->limit($limit) ->get(); $list = []; foreach ($models as $model) 
{
 $list[] = new TaskFileEntity($model->toArray()); 
}
 return $list; 
}
 /** * BatchUpdateFile file_key. * Using CASE WHEN Implementationonce BatchUpdate. * * @param array $updateBatch [['file_id' => 1, 'file_key' => 'new/path', 'updated_at' => '...'], ...] * @return int UpdateFileQuantity */ 
    public function batchUpdateFileKeys(array $updateBatch): int 
{
 if (empty($updateBatch)) 
{
 return 0; 
}
 $fileIds = array_column($updateBatch, 'file_id'); // Build CASE WHEN BindParameterCorrectorder  $fileKeyCases = []; $updatedAtCases = []; $fileKeyBindings = []; $updatedAtBindings = []; foreach ($updateBatch as $item) 
{
 $fileKeyCases[] = 'WHEN ? THEN ?'; $updatedAtCases[] = 'WHEN ? THEN ?'; // file_key Parameter $fileKeyBindings[] = $item['file_id']; $fileKeyBindings[] = $item['file_key']; // updated_at Parameter $updatedAtBindings[] = $item['file_id']; $updatedAtBindings[] = $item['updated_at']; 
}
 $fileKeyCasesSql = implode(' ', $fileKeyCases); $updatedAtCasesSql = implode(' ', $updatedAtCases); // Build SQLFollowCorrectorder MergeParameter $sql = sprintf( 'UPDATE %s SET file_key = CASE file_id %s END, updated_at = CASE file_id %s END WHERE file_id IN (%s)', $this->model->gettable (), $fileKeyCasesSql, $updatedAtCasesSql, implode(',', array_fill(0, count($fileIds), '?')) ); // CorrectParameterorder  file_key CASE updated_at CASEFinallyyes WHERE IN $bindings = array_merge($fileKeyBindings, $updatedAtBindings, $fileIds); return Db::update($sql, $bindings); 
}
 /** * Batchdelete Filedelete . */ 
    public function deleteByIds(array $fileIds): void 
{
 if (empty($fileIds)) 
{
 return; 
}
 $this->model::query() ->whereIn('file_id', $fileIds) ->forcedelete (); 
}
 /** * According toFileKeysBatchdelete Filedelete . */ 
    public function deleteByFileKeys(array $fileKeys): void 
{
 if (empty($fileKeys)) 
{
 return; 
}
 $this->model::query() ->whereIn('file_key', $fileKeys) ->forcedelete (); 
}
 /** * Getspecified Directoryunder MinimumSortValue. */ 
    public function getMinSortByParentId(?int $parentId, int $projectId): ?int 
{
 $query = $this->model::query() ->where('project_id', $projectId) ->whereNull('deleted_at'); if ($parentId === null) 
{
 $query->whereNull('parent_id'); 
}
 else 
{
 $query->where('parent_id', $parentId); 
}
 return $query->min('sort'); 
}
 /** * Getspecified Directoryunder MaximumSortValue. */ 
    public function getMaxSortByParentId(?int $parentId, int $projectId): ?int 
{
 $query = $this->model::query() ->where('project_id', $projectId) ->whereNull('deleted_at'); if ($parentId === null) 
{
 $query->whereNull('parent_id'); 
}
 else 
{
 $query->where('parent_id', $parentId); 
}
 return $query->max('sort'); 
}
 /** * Getspecified FileSortValue. */ 
    public function getSortByFileId(int $fileId): ?int 
{
 return $this->model::query() ->where('file_id', $fileId) ->whereNull('deleted_at') ->value('sort'); 
}
 /** * Getspecified SortValueafter NextSortValue. */ 
    public function getNextSortAfter(?int $parentId, int $currentSort, int $projectId): ?int 
{
 $query = $this->model::query() ->where('project_id', $projectId) ->where('sort', '>', $currentSort) ->whereNull('deleted_at'); if ($parentId === null) 
{
 $query->whereNull('parent_id'); 
}
 else 
{
 $query->where('parent_id', $parentId); 
}
 return $query->min('sort'); 
}
 /** * GetDirectoryunder AllNode. */ 
    public function getSiblingsByParentId(?int $parentId, int $projectId, string $orderBy = 'sort', string $direction = 'ASC'): array 
{
 $query = $this->model::query() ->where('project_id', $projectId) ->whereNull('deleted_at'); if ($parentId === null) 
{
 $query->whereNull('parent_id'); 
}
 else 
{
 $query->where('parent_id', $parentId); 
}
 return $query->orderBy($orderBy, $direction)->get()->toArray(); 
}
 
    public function getSiblingCountByParentId(int $parentId, int $projectId): int 
{
 return $this->model::query() ->where('project_id', $projectId) ->where('parent_id', $parentId) ->whereNull('deleted_at') ->count(); 
}
 /** * BatchUpdateSortValue. */ 
    public function batchUpdateSort(array $updates): void 
{
 if (empty($updates)) 
{
 return; 
}
 foreach ($updates as $update) 
{
 $this->model::query() ->where('file_id', $update['file_id']) ->update(['sort' => $update['sort'], 'updated_at' => date('Y-m-d H:i:s')]); 
}
 
}
 /** * Batch bind files to project with parent directory. * Updates both project_id and parent_id atomically. */ 
    public function batchBindToProject(array $fileIds, int $projectId, int $parentId): int 
{
 if (empty($fileIds)) 
{
 return 0; 
}
 return $this->model::query() ->whereIn('file_id', $fileIds) ->where(function ($query) 
{
 $query->whereNull('project_id') ->orWhere('project_id', 0); 
}
) ->update([ 'project_id' => $projectId, 'parent_id' => $parentId, 'updated_at' => date('Y-m-d H:i:s'), ]); 
}
 
    public function findLatestUpdatedByProjectId(int $projectId): ?TaskFileEntity 
{
 $model = $this->model::query() ->withTrashed() ->where('project_id', $projectId) ->orderBy('updated_at', 'desc') ->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileEntity($model->toArray()); 
}
 /** * Count files by project ID. */ 
    public function countFilesByProjectId(int $projectId): int 
{
 return $this->model::query() ->where('project_id', $projectId) ->where('storage_type', StorageType::WORKSPACE->value) ->where('is_hidden', false) ->whereNull('deleted_at') ->count(); 
}
 /** * Get files by project ID with resume support. * Used for fork migration with pagination and resume capability. */ 
    public function getFilesByProjectIdWithResume(int $projectId, ?int $lastFileId, int $limit): array 
{
 $query = $this->model::query() ->where('project_id', $projectId) ->where('storage_type', StorageType::WORKSPACE->value) ->where('is_hidden', false) ->whereNull('deleted_at') ->orderBy('file_id', 'asc') ->limit($limit); // Support resume from last file ID if ($lastFileId !== null) 
{
 $query->where('file_id', '>', $lastFileId); 
}
 $models = $query->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileEntity($model->toArray()); 
}
 return $entities; 
}
 /** * Batch update parent_id for multiple files. * Used for fixing parent relationships during fork operations. */ 
    public function batchUpdateParentId(array $fileIds, int $parentId, string $userId): int 
{
 if (empty($fileIds)) 
{
 return 0; 
}
 return $this->model::query() ->whereIn('file_id', $fileIds) ->whereNull('deleted_at') ->update([ 'parent_id' => $parentId, 'updated_at' => date('Y-m-d H:i:s'), ]); 
}
 
    public function lockDirectChildrenForUpdate(int $parentId): array 
{
 return $this->model::query() ->where('parent_id', $parentId) ->orderBy('sort', 'ASC') ->orderBy('file_id', 'ASC') ->lockForUpdate() ->get() ->toArray(); 
}
 
    public function getAllChildrenByParentId(int $parentId): array 
{
 return $this->model::query() ->where('parent_id', $parentId) ->orderBy('sort', 'ASC') ->orderBy('file_id', 'ASC') ->get() ->toArray(); 
}
 /** * Resumedelete File. */ 
    public function restoreFile(int $fileId): void 
{
 $this->model::withTrashed() ->where('file_id', $fileId) ->restore(); 
}
 
}
 
