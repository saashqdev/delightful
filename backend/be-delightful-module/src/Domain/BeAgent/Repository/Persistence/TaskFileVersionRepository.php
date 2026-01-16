<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileVersionEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileVersionRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskFileVersionModel;

class TaskFileVersionRepository implements TaskFileVersionRepositoryInterface 
{
 
    public function __construct(
    protected TaskFileVersionModel $model) 
{
 
}
 
    public function getById(int $id): ?TaskFileVersionEntity 
{
 $model = $this->model::query()->where('id', $id)->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileVersionEntity($model->toArray()); 
}
 
    public function getByFileId(int $fileId): array 
{
 $models = $this->model::query() ->where('file_id', $fileId) ->orderBy('version', 'desc') ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileVersionEntity($model->toArray()); 
}
 return $entities; 
}
 
    public function countByFileId(int $fileId): int 
{
 return $this->model::query() ->where('file_id', $fileId) ->count(); 
}
 
    public function getLatestVersionNumber(int $fileId): int 
{
 $latestVersion = $this->model::query() ->where('file_id', $fileId) ->max('version'); return $latestVersion ?? 0; 
}
 
    public function insert(TaskFileVersionEntity $entity): TaskFileVersionEntity 
{
 $date = date('Y-m-d H:i:s'); $entity->setCreatedAt($date); $entity->setUpdatedAt($date); $entityArray = $entity->toArray(); $model = $this->model::query()->create($entityArray); if (! empty($model->id)) 
{
 $entity->setId($model->id); 
}
 return $entity; 
}
 
    public function deleteOldVersionsByFileId(int $fileId, int $keepCount): int 
{
 // Getneed Cleanversion entities list $versionsTodelete = $this->getVersionsToCleanup($fileId, $keepCount); if (empty($versionsTodelete )) 
{
 return 0; 
}
 // VersionIDfor Batchdelete $idsTodelete = array_map(fn ($version) => $version->getId(), $versionsTodelete ); // Batchdelete OldVersion return $this->model::query() ->whereIn('id', $idsTodelete ) ->delete(); 
}
 
    public function deleteAllVersionsByFileId(int $fileId): int 
{
 return $this->model::query() ->where('file_id', $fileId) ->delete(); 
}
 /** * Getneed Cleanversion entities list . */ 
    public function getVersionsToCleanup(int $fileId, int $keepCount): array 
{
 // FirstGetneed VersionIDLatestkeepCountVersion $idsToKeep = $this->model::query() ->where('file_id', $fileId) ->orderBy('version', 'desc') ->limit($keepCount) ->pluck('id') ->toArray(); if (empty($idsToKeep)) 
{
 // IfDon't haveVersionReturn AllVersionfor Clean $models = $this->model::query() ->where('file_id', $fileId) ->get(); 
}
 else 
{
 // SecondGetneed delete Versionrecord $models = $this->model::query() ->where('file_id', $fileId) ->whereNotIn('id', $idsToKeep) ->get(); 
}
 $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileVersionEntity($model->toArray()); 
}
 return $entities; 
}
 /** * PagingGetspecified FileVersionlist VersionReverse. */ 
    public function getByFileIdWithPage(int $fileId, int $page, int $pageSize): array 
{
 $query = $this->model::query()->where('file_id', $fileId); // GetTotal $total = $query->count(); // Pagingquery $models = $query->orderBy('version', 'desc') ->skip(($page - 1) * $pageSize) ->take($pageSize) ->get(); // Convert to $entities = []; foreach ($models as $model) 
{
 $entities[] = new TaskFileVersionEntity($model->toArray()); 
}
 return [ 'list' => $entities, 'total' => $total, ]; 
}
 /** * According toFileIDVersionGetVersion. */ 
    public function getByFileIdAndVersion(int $fileId, int $version): ?TaskFileVersionEntity 
{
 $model = $this->model::query() ->where('file_id', $fileId) ->where('version', $version) ->first(); if (! $model) 
{
 return null; 
}
 return new TaskFileVersionEntity($model->toArray()); 
}
 
}
 
