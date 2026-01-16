<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\WorkspaceEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\WorkspaceRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\WorkspaceModel;
use Hyperf\DbConnection\Db;

class WorkspaceRepository extends AbstractRepository implements WorkspaceRepositoryInterface 
{
 
    public function __construct(
    protected WorkspaceModel $model) 
{
 
}
 /** * Getuser workspace list . */ 
    public function getuser Workspaces(string $userId, int $page, int $pageSize): array 
{
 $models = $this->model::query() ->where('user_id', $userId) ->orderBy('updated_at', 'desc') ->offset(($page - 1) * $pageSize) ->limit($pageSize) ->get(); $entities = []; foreach ($models as $model) 
{
 $entities[] = $this->modelToEntity($model); 
}
 return $entities; 
}
 /** * Createworkspace . */ 
    public function createWorkspace(WorkspaceEntity $workspaceEntity): WorkspaceEntity 
{
 $model = new $this->model(); $model->fill($workspaceEntity->toArray()); $model->save(); $workspaceEntity->setId($model->id); return $workspaceEntity; 
}
 /** * Updateworkspace . */ 
    public function updateWorkspace(WorkspaceEntity $workspaceEntity): bool 
{
 $model = $this->model::query()->find($workspaceEntity->getId()); if (! $model) 
{
 return false; 
}
 return $model->update($workspaceEntity->toArray()); 
}
 /** * Getworkspace Details. */ 
    public function getWorkspaceById(int $workspaceId): ?WorkspaceEntity 
{
 $model = $this->model::query()->where('id', $workspaceId)->whereNull('deleted_at')->first(); return $this->modelToEntity($model); 
}
 /** * According toIDFindworkspace . */ 
    public function findById(int $workspaceId): ?WorkspaceEntity 
{
 return $this->getWorkspaceById($workspaceId); 
}
 /** * ThroughSessionIDGetworkspace . */ 
    public function getWorkspaceByConversationId(string $conversationId): ?WorkspaceEntity 
{
 $model = $this->model::query()->where('conversation_id', $conversationId)->whereNull('deleted_at')->first(); return $this->modelToEntity($model); 
}
 /** * Updateworkspace Status. */ 
    public function updateWorkspaceArchivedStatus(int $workspaceId, int $isArchived): bool 
{
 return $this->model::query() ->where('id', $workspaceId) ->update(['is_archived' => $isArchived]) > 0; 
}
 /** * delete workspace . */ 
    public function deleteWorkspace(int $workspaceId): bool 
{
 return $this->model::query()->where('id', $workspaceId)->delete() > 0; 
}
 /** * delete workspace Associationtopic . */ 
    public function deleteTopicsByWorkspaceId(int $workspaceId): bool 
{
 // Attentionneed According toActualImplementationThroughExternalServiceor Repositorydelete topic // Don't havetopic table Structureonly as Example return Db::table('magic_chat_topics') ->where('workspace_id', $workspaceId) ->delete() > 0; 
}
 /** * Updateworkspace current topic . */ 
    public function updateWorkspacecurrent Topic(int $workspaceId, string $topicId): bool 
{
 return $this->model::query() ->where('id', $workspaceId) ->update(['current_topic_id' => $topicId]) > 0; 
}
 /** * Updateworkspace Status. */ 
    public function updateWorkspaceStatus(int $workspaceId, int $status): bool 
{
 return $this->model::query() ->where('id', $workspaceId) ->update(['status' => $status]) > 0; 
}
 /** * According toConditionGetworkspace list * SupportPagingSort. * * @param array $conditions query Condition * @param int $page Page number * @param int $pageSize Per pageQuantity * @param string $orderBy SortField * @param string $orderDirection Sort * @return array [total, list] Totalworkspace list */ 
    public function getWorkspacesByConditions( array $conditions = [], int $page = 1, int $pageSize = 10, string $orderBy = 'id', string $orderDirection = 'asc' ): array 
{
 $query = $this->model::query(); // DefaultFilterdelete dData $query->whereNull('deleted_at'); // Applyquery Condition foreach ($conditions as $field => $value) 
{
 // DefaultEqualquery $query->where($field, $value); 
}
 // GetTotal $total = $query->count(); // SortPaging $list = $query->orderBy($orderBy, $orderDirection) ->offset(($page - 1) * $pageSize) ->limit($pageSize) ->get(); // Convert toObject $entities = []; foreach ($list as $model) 
{
 $entities[] = $this->modelToEntity($model); 
}
 return [ 'total' => $total, 'list' => $entities, ]; 
}
 /** * Saveworkspace Createor Update. * * @param WorkspaceEntity $workspaceEntity workspace * @return WorkspaceEntity Saveworkspace */ 
    public function save(WorkspaceEntity $workspaceEntity): WorkspaceEntity 
{
 if ($workspaceEntity->getId()) 
{
 // UpdateAlready existsworkspace $model = $this->model::query()->find($workspaceEntity->getId()); if ($model) 
{
 $model->update($workspaceEntity->toArray()); 
}
 
}
 else 
{
 // CreateNewworkspace $model = new $this->model(); $model->fill($workspaceEntity->toArray()); $model->save(); $workspaceEntity->setId($model->id); 
}
 return $workspaceEntity; 
}
 /** * GetAllworkspace OrganizationCodelist . * * @return array OrganizationCodelist */ 
    public function getUniqueOrganizationCodes(): array 
{
 return $this->model::query() ->whereNull('deleted_at') ->distinct() ->pluck('user_organization_code') ->filter(function ($code) 
{
 return ! empty($code); 
}
) ->toArray(); 
}
 /** * BatchGetworkspace NameMap. * * @param array $workspaceIds workspace IDArray * @return array ['workspace_id' => 'workspace_name'] KeyValuePair */ 
    public function getWorkspaceNamesBatch(array $workspaceIds): array 
{
 if (empty($workspaceIds)) 
{
 return []; 
}
 $results = $this->model::query() ->whereIn('id', $workspaceIds) ->whereNull('deleted_at') ->select(['id', 'name']) ->get(); $workspaceNames = []; foreach ($results as $result) 
{
 $workspaceNames[(string) $result->id] = $result->name; 
}
 return $workspaceNames; 
}
 /** * ModelObjectConvert toObject * * @param null|WorkspaceModel $model ModelObject * @return null|WorkspaceEntity Object */ 
    protected function modelToEntity($model): ?WorkspaceEntity 
{
 if (! $model) 
{
 return null; 
}
 $entity = new WorkspaceEntity(); $entity->setId((int) $model->id); $entity->setuser Id((string) $model->user_id); $entity->setuser OrganizationCode((string) $model->user_organization_code); $entity->setChatConversationId((string) $model->chat_conversation_id); $entity->setName((string) $model->name); $entity->setIsArchived((int) $model->is_archived); $entity->setCreatedUid((string) $model->created_uid); $entity->setUpdatedUid((string) $model->updated_uid); $entity->setCreatedAt($model->created_at ? (string) $model->created_at : null); $entity->setUpdatedAt($model->updated_at ? (string) $model->updated_at : null); $entity->setdelete dAt($model->deleted_at ? (string) $model->deleted_at : null); $entity->setcurrent TopicId($model->current_topic_id ? (int) $model->current_topic_id : null); $entity->setStatus((int) $model->status); return $entity; 
}
 
}
 
