<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberEntity;
/** * ItemMemberRepository interface. * * ItemMemberData */

interface ProjectMemberRepositoryInterface 
{
 /** * BatchInsertItemMember. * * @param ProjectMemberEntity[] $projectMemberEntities ItemMemberArray */ 
    public function insert(array $projectMemberEntities): void; /** * According toProject IDdelete AllMember. * * @param int $projectId Project ID * @param array $roles Role * @return int delete record */ 
    public function deleteByProjectId(int $projectId, array $roles = []): int; /** * According toIDArrayBatchdelete Member. * * @param array $ids MemberIDArray * @return int delete record */ 
    public function deleteByIds(array $ids): int; /** * delete specified Itemuser MemberRelationship. * * @param int $projectId Project ID * @param string $userId user ID * @return int delete record */ 
    public function deleteByProjectAnduser (int $projectId, string $userId): int; /** * delete specified ItemTargetMemberRelationship. * * @param int $projectId Project ID * @param string $targetType TargetType * @param string $targetId TargetID * @return int delete record */ 
    public function deleteByProjectAndTarget(int $projectId, string $targetType, string $targetId): int; /** * check Itemuser MemberRelationshipwhether Exist. * * @param int $projectId Project ID * @param string $userId user ID * @return bool ExistReturn trueOtherwiseReturn false */ 
    public function existsByProjectAnduser (int $projectId, string $userId): bool; /** * check ItemDepartmentlist MemberRelationshipwhether Exist. * * @param int $projectId Project ID * @param array $departmentIds DepartmentIDArray * @return bool ExistReturn trueOtherwiseReturn false */ 
    public function existsByProjectAndDepartments(int $projectId, array $departmentIds): bool; /** * According toProject IDGetAllItemMember. * * @param int $projectId Project ID * @param array $roles Role * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function findByProjectId(int $projectId, array $roles = []): array; /** * According touser DepartmentGetProject IDlist Total. * * @param string $userId user ID * @param array $departmentIds DepartmentIDArray * @param null|string $name ItemNameVagueSearchCritical * @param null|string $sortField SortFieldupdated_at,created_at,last_active_at * @param array $organizationCodes organization code list for Filter * @return array ['total' => int, 'list' => array] */ 
    public function getProjectIdsByuser AndDepartments( string $userId, array $departmentIds = [], ?string $name = null, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [], ?string $joinMethod = null, array $organizationCodes = [] ): array; /** * BatchGetItemMemberTotal. * * @param array $projectIds Project IDArray * @return array [project_id => total_count] */ 
    public function getProjectMembersCounts(array $projectIds): array; /** * BatchGetproject's first Nmembers preview . * * @param array $projectIds Project IDArray * @param int $limit LimitQuantityDefault4 * @return array [project_id => [['target_type' => '', 'target_id' => ''], ...]] */ 
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array; /** * Getuser Createand HaveMemberProject IDlist Total. * * @return array ['total' => int, 'list' => array] */ 
    public function getSharedProjectIdsByuser ( string $userId, string $organizationCode, ?string $name = null, int $page = 1, int $pageSize = 10, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [] ): array; /** * Get list of creator user IDs for collaboration projects. * * @param string $userId current user ID * @param array $departmentIds user AtDepartmentIDArray * @param string $organizationCode OrganizationCode * @return array creator user IDArray */ 
    public function getCollaborationProjectcreator Ids( string $userId, array $departmentIds, string $organizationCode ): array; /** * Get list of projects user participates inSupportcollaboration ItemFilterworkspace BindFilter. * * @param string $userId user ID * @param int $workspaceId workspace ID * @param bool $showCollaboration whether Displaycollaboration Item * @param null|string $projectName ItemNameVagueSearch * @param int $page Page number * @param int $pageSize Per pageSize * @param string $sortField SortField * @param string $sortDirection Sort * @param null|array $organizationCodes organization code * @return array ['total' => int, 'list' => array] */ 
    public function getParticipatedProjects( string $userId, ?int $workspaceId, bool $showCollaboration = true, ?string $projectName = null, int $page = 1, int $pageSize = 10, string $sortField = 'last_active_at', string $sortDirection = 'desc', ?array $organizationCodes = null ): array; /** * According toProject IDuser IDGetItemMemberinfo . * * @param int $projectId Project ID * @param string $userId user ID * @return null|ProjectMemberEntity ItemMember */ 
    public function getMemberByProjectAnduser (int $projectId, string $userId): ?ProjectMemberEntity; /** * According toProject IDMemberIDArrayGetMemberlist . * * @param int $projectId Project ID * @param array $memberIds MemberIDArray * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function getMembersByIds(int $projectId, array $memberIds): array; /** * According toProject IDDepartmentIDArrayGetItemMemberlist . * * @param int $projectId Project ID * @param array $departmentIds DepartmentIDArray * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array; /** * BatchUpdateMemberpermission NewFormattarget_type + target_id. * * @param int $projectId Project ID * @param array $roleUpdates [['target_type' => '', 'target_id' => '', 'role' => ''], ...] * @return int Updaterecord */ 
    public function batchUpdateRole(int $projectId, array $roleUpdates): int; /** * Batchdelete Memberdelete . * * @param int $projectId Project ID * @param array $memberIds MemberIDArray * @return int delete record */ 
    public function deleteMembersByIds(int $projectId, array $memberIds): int; /** * ThroughAuthorTargetIDGetItemIdlist ExcludeOWNERRole. * * @param array $targetIds TargetIDArrayuser IDor DepartmentID * @return array ItemIds */ 
    public function getProjectIdsByCollaboratorTargets(array $targetIds, array $roles): array; /** * BatchGetuser AtItemin Memberrecord . * * @param array $projectIds Project IDArray * @param array $targetIds TargetIDArrayuser IDDepartmentID * @return ProjectMemberEntity[] MemberArray */ 
    public function getProjectMembersByTargetIds(array $projectIds, array $targetIds): array; 
}
 
