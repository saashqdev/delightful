<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberSettingEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberJoinMethod;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectMemberRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectMemberSettingRepositoryInterface;
use Hyperf\DbConnection\Db;
/** * ItemMemberService * * process ItemMemberrelated AllIncludepermission Validate Member */

class ProjectMemberDomainService 
{
 
    public function __construct( 
    private readonly ProjectMemberRepositoryInterface $projectMemberRepository, 
    private readonly ProjectMemberSettingRepositoryInterface $projectMemberSettingRepository, ) 
{
 
}
 /** * UpdateItemMember - MainMethod. * * @param ProjectMemberEntity[] $memberEntities MemberArray */ 
    public function updateProjectMembers( string $organizationCode, int $projectId, array $memberEntities ): void 
{
 // 1. Set project ID and organization code for each member entity foreach ($memberEntities as $memberEntity) 
{
 $memberEntity->setProjectId($projectId); $memberEntity->setOrganizationCode($organizationCode); 
}
 // 2. Execute update operation Db::transaction(function () use ($projectId, $memberEntities) 
{
 // delete AllHaveMember $this->projectMemberRepository->deleteByProjectId($projectId, [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value]);
// BatchInsertNewMember if (! empty($memberEntities)) 
{
 $this->projectMemberRepository->insert($memberEntities); 
}
 
}
); 
}
 /** * check user whether as Itemuser Member. */ 
    public function isProjectMemberByuser (int $projectId, string $userId): bool 
{
 return $this->projectMemberRepository->existsByProjectAnduser ($projectId, $userId); 
}
 /** * check user whether as ItemDepartmentMember. */ 
    public function isProjectMemberByDepartments(int $projectId, array $departmentIds): bool 
{
 return $this->projectMemberRepository->existsByProjectAndDepartments($projectId, $departmentIds); 
}
 /** * According toProject IDGetItemMemberlist . * * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function getProjectMembers(int $projectId, array $roles = []): array 
{
 return $this->projectMemberRepository->findByProjectId($projectId, $roles); 
}
 /** * According touser DepartmentGetProject IDlist . */ 
    public function deleteByProjectId(int $projectId): bool 
{
 return (bool) $this->projectMemberRepository->deleteByProjectId($projectId); 
}
 /** * According touser DepartmentGetProject IDlist Total. * * @return array ['total' => int, 'list' => array] */ 
    public function getProjectIdsByuser AndDepartmentsWithTotal( string $userId, array $departmentIds = [], ?string $name = null, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [], ?string $joinMethod = null, array $organizationCodes = [] ): array 
{
 return $this->projectMemberRepository->getProjectIdsByuser AndDepartments( $userId, $departmentIds, $name, $sortField, $sortDirection, $creatoruser Ids, $joinMethod, $organizationCodes ); 
}
 /** * BatchGetItemMemberTotal. * * @return array [project_id => total_count] */ 
    public function getProjectMembersCounts(array $projectIds): array 
{
 return $this->projectMemberRepository->getProjectMembersCounts($projectIds); 
}
 /** * BatchGetproject's first Nmembers preview . * * @return ProjectMemberEntity[][] */ 
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array 
{
 return $this->projectMemberRepository->getProjectMembersPreview($projectIds, $limit); 
}
 /** * Getuser Createand HaveMemberProject IDlist Total. * * @return array ['total' => int, 'list' => array] */ 
    public function getSharedProjectIdsByuser WithTotal( string $userId, string $organizationCode, ?string $name = null, int $page = 1, int $pageSize = 10, ?string $sortField = null, string $sortDirection = 'desc', array $creatoruser Ids = [] ): array 
{
 return $this->projectMemberRepository->getSharedProjectIdsByuser ( $userId, $organizationCode, $name, $page, $pageSize, $sortField, $sortDirection, $creatoruser Ids ); 
}
 /** * UpdateItempinned Status. */ 
    public function updateProjectPinStatus(string $userId, int $projectId, string $organizationCode, bool $isPinned): bool 
{
 // 1. check Datawhether ExistIfdoes not existCreateDefaultData $setting = $this->projectMemberSettingRepository->findByuser AndProject($userId, $projectId); if ($setting === null) 
{
 $this->projectMemberSettingRepository->create($userId, $projectId, $organizationCode); 
}
 // 2. Updatepinned Status return $this->projectMemberSettingRepository->updatePinStatus($userId, $projectId, $isPinned); 
}
 /** * Getuser pinned Project IDlist . * * @return array pinned Project IDArray */ 
    public function getuser PinnedProjectIds(string $userId, string $organizationCode): array 
{
 return $this->projectMemberSettingRepository->getPinnedProjectIds($userId, $organizationCode); 
}
 /** * BatchGetuser AtMultipleItemSet . * * @return array [project_id => ProjectMemberSettingEntity, ...] */ 
    public function getuser ProjectSet (string $userId, array $projectIds): array 
{
 return $this->projectMemberSettingRepository->findByuser AndProjects($userId, $projectIds); 
}
 /** * Updateuser AtItemin Finallyactive Time. */ 
    public function updateuser LastActiveTime(string $userId, int $projectId, string $organizationCode): bool 
{
 // 1. check Datawhether ExistIfdoes not existCreateDefaultData $setting = $this->projectMemberSettingRepository->findByuser AndProject($userId, $projectId); if ($setting === null) 
{
 $this->projectMemberSettingRepository->create($userId, $projectId, $organizationCode); 
}
 return $this->projectMemberSettingRepository->updateLastActiveTime($userId, $projectId); 
}
 /** * delete ItemCleanrelated MemberSet . */ 
    public function cleanupProjectSet (int $projectId): bool 
{
 $this->projectMemberSettingRepository->deleteByProjectId($projectId); return true; 
}
 /** * Get list of creator user IDs for collaboration projects. * * @param string $userId current user ID * @param array $departmentIds user AtDepartmentIDArray * @param string $organizationCode OrganizationCode * @return array creator user IDArray */ 
    public function getCollaborationProjectcreator Ids( string $userId, array $departmentIds, string $organizationCode ): array 
{
 return $this->projectMemberRepository->getCollaborationProjectcreator Ids( $userId, $departmentIds, $organizationCode ); 
}
 /** * Set Itemshortcut . * * @param string $userId user ID * @param int $projectId Project ID * @param int $workspaceId workspace ID * @param string $organizationCode organization code * @return bool Set SuccessReturn true */ 
    public function setProjectShortcut(string $userId, int $projectId, int $workspaceId, string $organizationCode): bool 
{
 return $this->projectMemberSettingRepository->setProjectShortcut($userId, $projectId, $workspaceId, $organizationCode); 
}
 /** * cancel Itemshortcut . * * @param string $userId user ID * @param int $projectId Project ID * @return bool cancel SuccessReturn true */ 
    public function cancelProjectShortcut(string $userId, int $projectId): bool 
{
 return $this->projectMemberSettingRepository->cancelProjectShortcut($userId, $projectId); 
}
 /** * check Itemwhether Set shortcut . * * @param string $userId user ID * @param int $projectId Project ID * @param int $workspaceId workspace ID * @return bool Set Return true */ 
    public function hasProjectShortcut(string $userId, int $projectId, int $workspaceId): bool 
{
 return $this->projectMemberSettingRepository->hasProjectShortcut($userId, $projectId, $workspaceId); 
}
 /** * Get list of projects user participates inSupportcollaboration ItemFilter. * * @param string $userId user ID * @param int $workspaceId workspace ID0table Limitworkspace  * @param bool $showCollaboration whether Displaycollaboration Item * @param null|string $projectName ItemNameVagueSearch * @param int $page Page number * @param int $pageSize Per pageSize * @param string $sortField SortField * @param string $sortDirection Sort * @param null|array $organizationCodes organization code * @return array ['total' => int, 'list' => array] */ 
    public function getParticipatedProjectsWithCollaboration( string $userId, int $workspaceId, bool $showCollaboration = true, ?string $projectName = null, int $page = 1, int $pageSize = 10, ?array $organizationCodes = null, string $sortField = 'last_active_at', string $sortDirection = 'desc', ): array 
{
 // Determinewhether Limitworkspace $limitWorkspace = $workspaceId > 0; return $this->projectMemberRepository->getParticipatedProjects( $userId, $limitWorkspace ? $workspaceId : null, $showCollaboration, $projectName, $page, $pageSize, $sortField, $sortDirection, $organizationCodes ); 
}
 /** * InitializeItemMemberSet . * * @param string $userId user ID * @param int $projectId Project ID * @param int $workspaceId workspace ID * @param string $organizationCode organization code */ 
    public function initializeProjectMemberAndSet ( string $userId, int $projectId, int $workspaceId, string $organizationCode ): void 
{
 // CreateItemMemberrecord Set as owner Role $memberEntity = new ProjectMemberEntity(); $memberEntity->setProjectId($projectId); $memberEntity->setTargetTypeFromString('user '); $memberEntity->setTargetId($userId); $memberEntity->setRole(MemberRole::OWNER); $memberEntity->setOrganizationCode($organizationCode); $memberEntity->setInvitedBy($userId); // BatchInsertMemberrecord $this->projectMemberRepository->insert([$memberEntity]); // CreateItemMemberSet record Bindworkspace  $this->projectMemberSettingRepository->setProjectShortcut($userId, $projectId, $workspaceId, $organizationCode); 
}
 /** * ThroughInviteLinkAddItemMember. * * @param string $projectId Project ID * @param string $userId user ID * @param MemberRole $role MemberRole * @param string $organizationCode organization code * @param string $invitedBy InviteID * @return ProjectMemberEntity CreateMember */ 
    public function addMemberByInvitation( string $projectId, string $userId, MemberRole $role, string $organizationCode, string $invitedBy ): ProjectMemberEntity 
{
 // check whether already yes Member $isExistingMember = $this->getMemberByProjectAnduser ((int) $projectId, $userId); if ($isExistingMember) 
{
 return $isExistingMember; 
}
 // create new ItemMemberrecord $memberEntity = new ProjectMemberEntity(); $memberEntity->setProjectId((int) $projectId); $memberEntity->setTargetTypeFromString(MemberType::USER->value); $memberEntity->setTargetId($userId); $memberEntity->setRole($role); $memberEntity->setOrganizationCode($organizationCode); $memberEntity->setInvitedBy($invitedBy); $memberEntity->setJoinMethod(MemberJoinMethod::LINK); // InsertMemberrecord $this->projectMemberRepository->insert([$memberEntity]); return $memberEntity; 
}
 /** * delete specified user ItemMemberRelationship. * * @param int $projectId Project ID * @param string $userId user ID * @return bool delete whether Success */ 
    public function removeMemberByuser (int $projectId, string $userId): bool 
{
 $deletedCount = $this->projectMemberRepository->deleteByProjectAnduser ($projectId, $userId); return $deletedCount > 0; 
}
 /** * delete specified user TargetTypeItemMemberRelationship. * * @param int $projectId Project ID * @param string $targetType TargetTypeuser /Department * @param string $targetId TargetID * @return bool delete whether Success */ 
    public function removeMemberByTarget(int $projectId, string $targetType, string $targetId): bool 
{
 $deletedCount = $this->projectMemberRepository->deleteByProjectAndTarget($projectId, $targetType, $targetId); return $deletedCount > 0; 
}
 /** * According toProject IDuser IDGetItemMemberinfo . * * @param int $projectId Project ID * @param string $userId user ID * @return null|ProjectMemberEntity ItemMember */ 
    public function getMemberByProjectAnduser (int $projectId, string $userId): ?ProjectMemberEntity 
{
 return $this->projectMemberRepository->getMemberByProjectAnduser ($projectId, $userId); 
}
 /** * According toProject IDDepartmentIDArrayGetItemMemberlist . * * @param int $projectId Project ID * @param array $departmentIds DepartmentIDArray * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array 
{
 return $this->projectMemberRepository->getMembersByProjectAndDepartmentIds($projectId, $departmentIds); 
}
 /** * According toProject IDMemberIDArrayGetMemberlist . * * @param int $projectId Project ID * @param array $memberIds MemberIDArray * @return ProjectMemberEntity[] ItemMemberArray */ 
    public function getMembersByIds(int $projectId, array $memberIds): array 
{
 return $this->projectMemberRepository->getMembersByIds((int) $projectId, $memberIds); 
}
 /** * BatchUpdateMemberpermission NewFormattarget_type + target_id. * * @param int $projectId Project ID * @param array $roleUpdates [['target_type' => '', 'target_id' => '', 'role' => ''], ...] * @return int Updaterecord */ 
    public function batchUpdateRole(int $projectId, array $roleUpdates): int 
{
 $updateData = []; foreach ($roleUpdates as $member) 
{
 $memberRole = MemberRole::validatepermission Level($member['role']); $updateData[] = [ 'target_type' => $member['target_type'], 'target_id' => $member['target_id'], 'role' => $memberRole->value, ]; 
}
 return $this->projectMemberRepository->batchUpdateRole($projectId, $updateData); 
}
 /** * Batchdelete Member. * * @param int $projectId Project ID * @param array $memberIds MemberIDArray * @return int delete record */ 
    public function deleteMembersByIds(int $projectId, array $memberIds): int 
{
 return $this->projectMemberRepository->deleteMembersByIds($projectId, $memberIds); 
}
 /** * AddItemMemberInternalInvite. * * @param ProjectMemberEntity[] $memberEntities MemberArray * @param string $organizationCode organization code */ 
    public function addInternalMembers(array $memberEntities, string $organizationCode): void 
{
 if (empty($memberEntities)) 
{
 return; 
}
 // as each MemberSet organization code foreach ($memberEntities as $memberEntity) 
{
 $memberEntity->setJoinMethod(MemberJoinMethod::INTERNAL); $memberEntity->setOrganizationCode($organizationCode); 
}
 // BatchInsertMember $this->projectMemberRepository->insert($memberEntities); 
}
 
    public function getProjectIdsByCollaboratorTargets(array $targetIds): array 
{
 $roles = [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value]; return $this->projectMemberRepository->getProjectIdsByCollaboratorTargets($targetIds, $roles); 
}
 /** * BatchGetuser AtItemin highest permission Role. * * @param array $projectIds Project IDArray * @param array $targetIds TargetIDArrayuser IDDepartmentID * @return array [project => role] Project IDMap toRole */ 
    public function getuser HighestRolesInProjects(array $projectIds, array $targetIds): array 
{
 // 1. Get member entity data from repository $memberEntities = $this->projectMemberRepository->getProjectMembersByTargetIds($projectIds, $targetIds); if (empty($memberEntities)) 
{
 return []; 
}
 // 2. ItemGroupCalculate each Itemhighest permission Role $projectRoles = []; foreach ($memberEntities as $entity) 
{
 $projectId = $entity->getProjectId(); $role = $entity->getRole(); $permissionLevel = $role->getpermission Level(); // IfItemDon't haverecord or current Rolepermission Update if (! isset($projectRoles[$projectId]) || $permissionLevel > $projectRoles[$projectId]['level']) 
{
 $projectRoles[$projectId] = [ 'role' => $role->value, 'level' => $permissionLevel, ]; 
}
 
}
 // 3. Return RoleValueNot containpermission return array_map(fn ($data) => $data['role'], $projectRoles); 
}
 
}
 
