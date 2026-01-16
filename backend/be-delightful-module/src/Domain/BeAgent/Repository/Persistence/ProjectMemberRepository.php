<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectMemberRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\ProjectMemberModel;
use Hyperf\DbConnection\Db;
/** * ItemMemberImplementation. * * ItemMemberData */

class ProjectMemberRepository implements ProjectMemberRepositoryInterface 
{
 
    public function __construct( 
    private readonly ProjectMemberModel $projectMemberModel ) 
{
 
}
 /** * BatchInsertItemMember. */ 
    public function insert(array $projectMemberEntities): void 
{
 if (empty($projectMemberEntities)) 
{
 return; 
}
 $attributes = $this->prepareBatchInsertAttributes($projectMemberEntities); // UsingTransactionEnsureDataConsistent Db::transaction(function () use ($attributes) 
{
 // InsertInsertDatatoo much $chunks = array_chunk($attributes, 100);
foreach ($chunks as $chunk) 
{
 $this->projectMemberModel::query()->insert($chunk); 
}
 
}
); 
}
 /** * According toProject IDdelete AllMember. */ 
    public function deleteByProjectId(int $projectId, array $roles = []): int 
{
 $query = $this->projectMemberModel::query(); if (! empty($roles)) 
{
 $query = $query->whereIn('role', $roles); 
}
 return $query->where('project_id', $projectId)->delete(); 
}
 /** * According toIDArrayBatchdelete Member. */ 
    public function deleteByIds(array $ids): int 
{
 if (empty($ids)) 
{
 return 0; 
}
 return $this->projectMemberModel::query() ->whereIn('id', $ids) ->delete(); 
}
 /** * delete specified Itemuser MemberRelationship. */ 
    public function deleteByProjectAnduser (int $projectId, string $userId): int 
{
 return $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', 'user ') ->where('target_id', $userId) ->delete(); 
}
 /** * delete specified ItemTargetMemberRelationship. */ 
    public function deleteByProjectAndTarget(int $projectId, string $targetType, string $targetId): int 
{
 return $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', $targetType) ->where('target_id', $targetId) ->delete(); 
}
 /** * check Itemuser MemberRelationshipwhether Exist. */ 
    public function existsByProjectAnduser (int $projectId, string $userId): bool 
{
 return $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', MemberType::USER->value) ->where('target_id', $userId) ->exists(); 
}
 /** * check ItemDepartmentlist MemberRelationshipwhether Exist. */ 
    public function existsByProjectAndDepartments(int $projectId, array $departmentIds): bool 
{
 if (empty($departmentIds)) 
{
 return false; 
}
 return $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', MemberType::DEPARTMENT->value) ->whereIn('target_id', $departmentIds) ->exists(); 
}
 /** * According toProject IDGetAllItemMember. * * @param int $projectId Project ID * @param array $roles MemberRole * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function findByProjectId(int $projectId, array $roles = []): array 
{
 $query = $this->projectMemberModel::query()->where('project_id', $projectId); if (! empty($roles)) 
{
 $query->whereIn('role', $roles); 
}
 $results = $query->orderBy('id', 'asc')->get()->toArray(); $entities = []; foreach ($results as $row) 
{
 $entities[] = ProjectMemberEntity::modelToEntity($row); 
}
 return $entities; 
}
 /** * According touser DepartmentGetProject IDlist TotalSupportpinned Sort. */ 
    public function getProjectIdsByuser AndDepartments( string $userId, array $departmentIds = [], ?string $name = null, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [], ?string $joinMethod = null, array $organizationCodes = [] ): array 
{
 $query = $this->projectMemberModel::query() ->where(function ($query) use ($userId, $departmentIds) 
{
 $query->where(function ($subquery ) use ($userId) 
{
 $subquery ->where('target_type', MemberType::USER->value) ->where('target_id', $userId);

}
); if (! empty($departmentIds)) 
{
 $query->orWhere(function ($subquery ) use ($departmentIds) 
{
 $subquery ->where('target_type', MemberType::DEPARTMENT->value) ->whereIn('target_id', $departmentIds);

}
); 
}
 
}
); $query->join('magic_super_agent_project', 'magic_super_agent_project_members.project_id', '=', 'magic_super_agent_project.id') ->leftJoin('magic_super_agent_project_member_settings', function ($join) use ($userId) 
{
 $join->on('magic_super_agent_project_member_settings.project_id', '=', 'magic_super_agent_project.id') ->where('magic_super_agent_project_member_settings.user_id', '=', $userId);

}
) ->where('magic_super_agent_project.user_id', '!=', $userId) ->where('magic_super_agent_project.is_collaboration_enabled', 1) ->whereNull('magic_super_agent_project.deleted_at'); if (! empty($name)) 
{
 // IfHaveItemNameSearchConditionneed JoinItemtable $query->where('magic_super_agent_project.project_name', 'like', '%' . $name . '%'); 
}
 if (! empty($creatoruser Ids)) 
{
 // IfHavecreator user IDSearchCondition $query->whereIn('magic_super_agent_project.user_id', $creatoruser Ids); 
}
 if (! empty($joinMethod)) 
{
 // Join $query->where('magic_super_agent_project_members.join_method', $joinMethod); 
}
 if (! empty($organizationCodes)) 
{
 // According toorganization code Filter $query->whereIn('magic_super_agent_project.user_organization_code', $organizationCodes); 
}
 $query->select( 'magic_super_agent_project_members.project_id', 'magic_super_agent_project.updated_at', 'magic_super_agent_project.created_at', 'magic_super_agent_project_member_settings.is_pinned', 'magic_super_agent_project_member_settings.last_active_at', 'magic_super_agent_project_member_settings.is_bind_workspace', 'magic_super_agent_project_member_settings.bind_workspace_id' ) ->distinct() ->orderByRaw('COALESCE(magic_super_agent_project_member_settings.is_pinned, 0) DESC'); // Pinned at front // According toSortFieldRowSortDefault updated_at Sort $effectiveSortField = $sortField ?: 'updated_at'; $effectiveSortDirection = $sortDirection ?: 'desc'; switch ($effectiveSortField) 
{
 case 'updated_at': $query->orderBy('magic_super_agent_project.updated_at', $effectiveSortDirection); break; case 'created_at': $query->orderBy('magic_super_agent_project.created_at', $effectiveSortDirection); break; case 'last_active_at': $query->orderBy('magic_super_agent_project_member_settings.last_active_at', $effectiveSortDirection); break; default: $query->orderBy('magic_super_agent_project.updated_at', 'desc'); break; 
}
 $results = $query->get()->toArray(); return [ 'total' => count($results), 'list' => $results, ]; 
}
 /** * BatchGetItemMemberTotal. * * @param array $projectIds Project IDArray * @return array [project_id => total_count] */ 
    public function getProjectMembersCounts(array $projectIds): array 
{
 if (empty($projectIds)) 
{
 return []; 
}
 // Usingquery optimize N+1 $results = $this->projectMemberModel::query() ->whereIn('project_id', $projectIds) ->whereIn('role', [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value]) ->groupBy('project_id') ->selectRaw('project_id, COUNT(*) as total_count') ->get() ->keyBy('project_id') ->toArray(); // EnsureAllProject IDHaveReturn valueDon't haveMemberItemReturn 0 $counts = []; foreach ($projectIds as $projectId) 
{
 $counts[$projectId] = (int) ($results[$projectId]['total_count'] ?? 0); 
}
 return $counts; 
}
 /** * BatchGetproject's first Nmembers preview . * * @param array $projectIds Project IDArray * @param int $limit LimitQuantityDefault4 * @return array [project_id => [ProjectMemberEntity[], ...]] */ 
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array 
{
 if (empty($projectIds)) 
{
 return []; 
}
 // UsingEloquentquery BatchGetAllrelated ItemMember $results = $this->projectMemberModel::query() ->whereIn('project_id', $projectIds) ->orderBy('id', 'asc') ->get() ->toArray(); // InitializeResultArray $preview = []; foreach ($projectIds as $projectId) 
{
 $preview[$projectId] = []; 
}
 // ItemGroupLimiteach ItemMemberQuantity $memberCounts = []; foreach ($results as $member) 
{
 $projectId = $member['project_id']; // InitializeCount if (! isset($memberCounts[$projectId])) 
{
 $memberCounts[$projectId] = 0; 
}
 // IfReachLimitQuantityAddResultin if ($memberCounts[$projectId] < $limit) 
{
 $preview[$projectId][] = ProjectMemberEntity::modelToEntity($member); ++$memberCounts[$projectId]; 
}
 
}
 return $preview; 
}
 /** * Getuser Createand HaveMemberProject IDlist TotalSupportpinned Sort. */ 
    public function getSharedProjectIdsByuser ( string $userId, string $organizationCode, ?string $name = null, int $page = 1, int $pageSize = 10, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [] ): array 
{
 // BuildBasequery Finduser Createand HaveMemberItem $query = $this->projectMemberModel::query() ->join('magic_super_agent_project', 'magic_super_agent_project_members.project_id', '=', 'magic_super_agent_project.id') ->leftJoin('magic_super_agent_project_member_settings', function ($join) use ($userId) 
{
 $join->on('magic_super_agent_project_member_settings.project_id', '=', 'magic_super_agent_project.id') ->where('magic_super_agent_project_member_settings.user_id', '=', $userId);

}
) ->whereIn('magic_super_agent_project_members.role', [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value]) ->where('magic_super_agent_project.user_id', '=', $userId) ->where('magic_super_agent_project.user_organization_code', '=', $organizationCode) ->where('magic_super_agent_project.is_collaboration_enabled', 1) ->whereNull('magic_super_agent_project.deleted_at'); // IfHaveItemNameSearchCondition if (! empty($name)) 
{
 $query->where('magic_super_agent_project.project_name', 'like', '%' . $name . '%'); 
}
 // IfHavecreator user IDSearchConditionForsharedTypeusually yes current user CreateItemPrimaryyes as ed InterfaceConsistent if (! empty($creatoruser Ids)) 
{
 $query->whereIn('magic_super_agent_project.user_id', $creatoruser Ids); 
}
 // GetTotal $totalquery = clone $query; $total = $totalquery ->select('magic_super_agent_project_members.project_id')->distinct()->count(); // Pagingquery Project IDincluding SortFieldCompatibleDISTINCT $projects = $query->select( 'magic_super_agent_project_members.project_id', 'magic_super_agent_project.updated_at', 'magic_super_agent_project.created_at', 'magic_super_agent_project_member_settings.is_pinned', 'magic_super_agent_project_member_settings.last_active_at', 'magic_super_agent_project_member_settings.is_bind_workspace', 'magic_super_agent_project_member_settings.bind_workspace_id' ) ->distinct() ->orderByRaw('COALESCE(magic_super_agent_project_member_settings.is_pinned, 0) DESC'); // Pinned at front // According toSortFieldRowSortDefault updated_at Sort $effectiveSortField = $sortField ?: 'updated_at'; $effectiveSortDirection = $sortDirection ?: 'desc'; switch ($effectiveSortField) 
{
 case 'updated_at': $projects->orderBy('magic_super_agent_project.updated_at', $effectiveSortDirection); break; case 'created_at': $projects->orderBy('magic_super_agent_project.created_at', $effectiveSortDirection); break; case 'last_active_at': $projects->orderBy('magic_super_agent_project_member_settings.last_active_at', $effectiveSortDirection); break; default: $projects->orderBy('magic_super_agent_project.updated_at', 'desc'); break; 
}
 $projects = $projects->offset(($page - 1) * $pageSize) ->limit($pageSize) ->get() ->toArray(); return [ 'total' => $total, 'list' => $projects, ]; 
}
 /** * Get list of creator user IDs for collaboration projects. */ 
    public function getCollaborationProjectcreator Ids( string $userId, array $departmentIds, string $organizationCode ): array 
{
 $query = $this->projectMemberModel::query() ->leftJoin('magic_super_agent_project as projects', 'magic_super_agent_project_members.project_id', '=', 'projects.id') ->where('magic_super_agent_project_members.organization_code', $organizationCode); // Builduser permission query Condition - user yes ItemMemberor DepartmentMember $query->where(function ($q) use ($userId, $departmentIds) 
{
 $q->where(function ($userquery ) use ($userId) 
{
 $userquery ->where('magic_super_agent_project_members.target_type', 'user ') ->where('magic_super_agent_project_members.target_id', $userId);

}
); if (! empty($departmentIds)) 
{
 $q->orWhere(function ($deptquery ) use ($departmentIds) 
{
 $deptquery ->where('magic_super_agent_project_members.target_type', 'Department') ->whereIn('magic_super_agent_project_members.target_id', $departmentIds);

}
); 
}
 
}
); // GetStatusNormalItemMember $query->where('magic_super_agent_project_members.status', '1'); // creator IDGroupdeduplication GetDuplicatecreator IDlist $creatorIds = $query->select('projects.user_id') ->whereNotNull('projects.user_id') ->groupBy('projects.user_id') ->pluck('projects.user_id') ->toArray(); return array_filter($creatorIds); // FilterNull 
}
 /** * Get list of projects user participates inSupportcollaboration ItemFilterworkspace BindFilter. */ 
    public function getParticipatedProjects( string $userId, ?int $workspaceId, bool $showCollaboration = true, ?string $projectName = null, int $page = 1, int $pageSize = 10, string $sortField = 'last_active_at', string $sortDirection = 'desc', ?array $organizationCodes = null ): array 
{
 // BuildBasequery $query = $this->projectMemberModel::query() ->from('magic_super_agent_project_members as pm') ->join('magic_super_agent_project as p', 'pm.project_id', '=', 'p.id') ->leftJoin('magic_super_agent_project_member_settings as pms', function ($join) use ($userId) 
{
 $join->on('p.id', '=', 'pms.project_id') ->where('pms.user_id', '=', $userId);

}
) ->where('pm.target_type', MemberType::USER->value) ->where('pm.target_id', $userId) ->where('pm.status', 1) ->where('p.project_status', 1) // query ActiveStatusItem ->whereNull('p.deleted_at'); // workspace LimitOptional if ($workspaceId !== null) 
{
 $query->where(function ($subquery ) use ($workspaceId, $userId) 
{
 $subquery // 1user CreateItemAtspecified workspace ->where(function ($q) use ($workspaceId, $userId) 
{
 $q->where('p.user_id', $userId) ->where('p.workspace_id', $workspaceId);

}
) // 2collaboration Itemuser Bindspecified workspace ->orWhere(function ($q) use ($workspaceId, $userId) 
{
 $q->where('p.user_id', '!=', $userId) ->where('pms.is_bind_workspace', 1) ->where('pms.bind_workspace_id', $workspaceId);

}
); 
}
); 
}
 // ItemNameVagueSearch if (! empty($projectName)) 
{
 $query->where('p.project_name', 'like', '%' . $projectName . '%'); 
}
 // OrganizationFilter if (! empty($organizationCodes)) 
{
 $query->whereIn('p.user_organization_code', $organizationCodes); 
}
 // collaboration ItemFilter if (! $showCollaboration) 
{
 // showCollaboration = 0 DisplaySet shortcut Item $query->where('pms.is_bind_workspace', 1); // Ifspecified ed workspace Limitas Bindworkspace Item if ($workspaceId !== null) 
{
 $query->where('pms.bind_workspace_id', $workspaceId); 
}
 
}
 // showCollaboration = 1 DisplayAllparticipated ItemDefault // GetTotal $totalquery = clone $query; $total = $totalquery ->select('p.id')->distinct()->count(); // Buildquery Field $query->select([ 'p.id', 'p.workspace_id', 'p.project_name', 'p.project_description', 'p.work_dir', 'p.current_topic_id', 'p.current_topic_status', 'p.project_status', 'p.project_mode', 'p.user_id', 'p.user_organization_code as organization_code', 'p.is_collaboration_enabled', 'p.default_join_permission', 'p.created_at', 'p.updated_at', 'pm.role as user_role', Db::raw('COALESCE(pms.is_pinned, 0) as is_pinned'), Db::raw('COALESCE(pms.is_bind_workspace, 0) as is_bind_workspace'), Db::raw('COALESCE(pms.bind_workspace_id, 0) as bind_workspace_id'), Db::raw('COALESCE(pms.last_active_at, p.created_at) as last_active_at'), 'pms.pinned_at', Db::raw('CASE WHEN p.user_id != ? THEN 1 ELSE 0 END as is_collaborator'), ]) ->addBinding($userId, 'select'); // deduplication $query->distinct(); // Sortpinned Pinned items sort by pin timeFinallySort by active time $query->orderByRaw('COALESCE(pms.is_pinned, 0) DESC'); // 1. Pinned at front $query->orderByRaw('pms.pinned_at DESC'); // 2. Pinned items sort by pin timeFinallyPinned at front $query->orderByRaw('COALESCE(pms.last_active_at, p.created_at) DESC'); // 3. Sort by active time // Paging $offset = ($page - 1) * $pageSize; $projects = $query->offset($offset) ->limit($pageSize) ->get() ->toArray(); return [ 'total' => $total, 'list' => $projects, ]; 
}
 /** * According toProject IDuser IDGetItemMemberinfo . */ 
    public function getMemberByProjectAnduser (int $projectId, string $userId): ?ProjectMemberEntity 
{
 $memberData = $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', MemberType::USER->value) ->where('target_id', $userId) ->first(); if (! $memberData) 
{
 return null; 
}
 return $this->toEntity($memberData->toArray()); 
}
 /** * According toProject IDMemberIDArrayGetMemberlist . */ 
    public function getMembersByIds(int $projectId, array $memberIds): array 
{
 if (empty($memberIds)) 
{
 return []; 
}
 $membersData = $this->projectMemberModel::query() ->where('project_id', $projectId) ->whereIn('target_id', $memberIds) ->get(); $entities = []; foreach ($membersData as $memberData) 
{
 $entities[] = $this->toEntity($memberData->toArray()); 
}
 return $entities; 
}
 /** * According toProject IDDepartmentIDArrayGetItemMemberlist . */ 
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array 
{
 if (empty($departmentIds)) 
{
 return []; 
}
 $membersData = $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', 'Department') ->whereIn('target_id', $departmentIds) ->get(); $entities = []; foreach ($membersData as $memberData) 
{
 $entities[] = $this->toEntity($memberData->toArray()); 
}
 return $entities; 
}
 /** * BatchUpdateMemberpermission NewFormattarget_type + target_id. */ 
    public function batchUpdateRole(int $projectId, array $roleUpdates): int 
{
 if (empty($roleUpdates)) 
{
 return 0; 
}
 $now = date('Y-m-d H:i:s'); $updatedCount = 0; foreach ($roleUpdates as $update) 
{
 $targetType = $update['target_type']; $targetId = $update['target_id']; $role = $update['role']; $result = $this->projectMemberModel::query() ->where('project_id', $projectId) ->where('target_type', $targetType) ->where('target_id', $targetId) ->update([ 'role' => $role, 'updated_at' => $now, ]); $updatedCount += $result; 
}
 return $updatedCount; 
}
 /** * Batchdelete Memberdelete . */ 
    public function deleteMembersByIds(int $projectId, array $memberIds): int 
{
 if (empty($memberIds)) 
{
 return 0; 
}
 // Usingdelete as table Don't havedeleted_atField return $this->projectMemberModel::query() ->where('project_id', $projectId) ->whereIn('target_id', $memberIds) ->delete(); 
}
 /** * ThroughAuthorTargetIDGetItemIdslist ExcludeOWNERRole. */ 
    public function getProjectIdsByCollaboratorTargets(array $targetIds, array $roles): array 
{
 if (empty($targetIds)) 
{
 return []; 
}
 return $this->projectMemberModel::query() ->whereIn('target_id', $targetIds) ->whereIn('role', $roles) ->where('status', MemberStatus::ACTIVE->value) ->distinct() ->pluck('project_id') ->toArray(); 
}
 /** * BatchGetuser AtItemin Memberrecord . */ 
    public function getProjectMembersByTargetIds(array $projectIds, array $targetIds): array 
{
 if (empty($projectIds) || empty($targetIds)) 
{
 return []; 
}
 $results = $this->projectMemberModel::query() ->whereIn('project_id', $projectIds) ->whereIn('target_id', $targetIds) ->where('status', MemberStatus::ACTIVE->value) ->get() ->toArray(); $entities = []; foreach ($results as $row) 
{
 $entities[] = ProjectMemberEntity::modelToEntity($row); 
}
 return $entities; 
}
 /** * BatchInsertPropertyArray. */ 
    private function prepareBatchInsertAttributes(array $projectMemberEntities): array 
{
 $attributes = []; foreach ($projectMemberEntities as $entity) 
{
 $memberAttrs = $this->entityToModelAttributes($entity); if ($entity->getId() === 0) 
{
 $snowId = IdGenerator::getSnowId(); $memberAttrs['id'] = $snowId; $entity->setId($snowId); 
}
 $attributes[] = $memberAttrs; 
}
 return $attributes; 
}
 /** * Convert toModelProperty. */ 
    private function entityToModelAttributes(ProjectMemberEntity $entity): array 
{
 $now = date('Y-m-d H:i:s'); return [ 'id' => $entity->getId(), 'project_id' => $entity->getProjectId(), 'target_type' => $entity->getTargetType()->value, 'target_id' => $entity->getTargetId(), 'role' => $entity->getRole()->value, 'organization_code' => $entity->getOrganizationCode(), 'status' => $entity->getStatus()->value, 'invited_by' => $entity->getInvitedBy(), 'join_method' => $entity->getJoinMethod()->value, 'created_at' => $now, 'updated_at' => $now, ]; 
}
 /** * DataArrayConvert toProjectMemberEntity. */ 
    private function toEntity(array $data): ProjectMemberEntity 
{
 // process target_typeFieldTypeConvert $targetType = $data['target_type'] ?? MemberType::USER->value; if (is_string($targetType)) 
{
 $targetType = MemberType::from($targetType); 
}
 // process roleFieldTypeConvert $role = $data['role'] ?? MemberRole::EDITOR->value; if (is_string($role)) 
{
 $role = MemberRole::from($role); 
}
 // process statusFieldTypeConvert $status = $data['status'] ?? MemberStatus::ACTIVE->value; if (is_int($status)) 
{
 $status = MemberStatus::from($status); 
}
 return new ProjectMemberEntity([ 'id' => $data['id'] ?? 0, 'project_id' => $data['project_id'] ?? 0, 'target_type' => $targetType, 'target_id' => $data['target_id'] ?? '', 'role' => $role, 'organization_code' => $data['organization_code'] ?? '', 'status' => $status, 'invited_by' => $data['invited_by'] ?? '', 'created_at' => $data['created_at'] ?? null, 'updated_at' => $data['updated_at'] ?? null, 'deleted_at' => $data['deleted_at'] ?? null, ]); 
}
 
}
 
