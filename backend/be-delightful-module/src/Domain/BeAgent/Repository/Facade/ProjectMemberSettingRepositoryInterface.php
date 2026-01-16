<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberSettingEntity;
/** * ItemMemberSet Repository interface. * * ItemMemberSet Data */

interface ProjectMemberSettingRepositoryInterface 
{
 /** * According touser IDProject IDFindSet . * * @param string $userId user ID * @param int $projectId Project ID * @return null|ProjectMemberSettingEntity Set or null */ 
    public function findByuser AndProject(string $userId, int $projectId): ?ProjectMemberSettingEntity; /** * CreateItemMemberSet . * * @param string $userId user ID * @param int $projectId Project ID * @param string $organizationCode organization code * @return ProjectMemberSettingEntity CreateSet */ 
    public function create(string $userId, int $projectId, string $organizationCode): ProjectMemberSettingEntity; /** * Createor UpdateItemMemberSet . * * @param ProjectMemberSettingEntity $entity Set * @return ProjectMemberSettingEntity Save */ 
    public function save(ProjectMemberSettingEntity $entity): ProjectMemberSettingEntity; /** * Updatepinned StatusFalserecord Already exists. * * @param string $userId user ID * @param int $projectId Project ID * @param bool $isPinned whether pinned * @return bool UpdateSuccessReturn true */ 
    public function updatePinStatus(string $userId, int $projectId, bool $isPinned): bool; /** * BatchGetuser pinned Project IDlist . * * @param string $userId user ID * @param string $organizationCode organization code * @return array pinned Project IDArray */ 
    public function getPinnedProjectIds(string $userId, string $organizationCode): array; /** * BatchGetuser AtMultipleItemSet . * * @param string $userId user ID * @param array $projectIds Project IDArray * @return array [project_id => ProjectMemberSettingEntity, ...] */ 
    public function findByuser AndProjects(string $userId, array $projectIds): array; /** * UpdateFinallyactive Time. * * @param string $userId user ID * @param int $projectId Project ID * @return bool UpdateSuccessReturn true */ 
    public function updateLastActiveTime(string $userId, int $projectId): bool; /** * delete Itemrelated AllSet . * * @param int $projectId Project ID * @return int delete record */ 
    public function deleteByProjectId(int $projectId): int; /** * delete user related AllSet . * * @param string $userId user ID * @param string $organizationCode organization code * @return int delete record */ 
    public function deleteByuser (string $userId, string $organizationCode): int; /** * Set Itemshortcut Bindworkspace . * * @param string $userId user ID * @param int $projectId Project ID * @param int $workspaceId workspace ID * @param string $organizationCode organization code * @return bool Set SuccessReturn true */ 
    public function setProjectShortcut(string $userId, int $projectId, int $workspaceId, string $organizationCode): bool; /** * cancel Itemshortcut cancel workspace Bind. * * @param string $userId user ID * @param int $projectId Project ID * @return bool cancel SuccessReturn true */ 
    public function cancelProjectShortcut(string $userId, int $projectId): bool; /** * check Itemwhether Set shortcut . * * @param string $userId user ID * @param int $projectId Project ID * @param int $workspaceId workspace ID * @return bool Set Return true */ 
    public function hasProjectShortcut(string $userId, int $projectId, int $workspaceId): bool; 
}
 
