<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberSettingEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberJoinMethod;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectMemberRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectMemberSettingRepositoryInterface;
use Hyperf\DbConnection\Db;

/**
 * Project Member Domain Service
 *
 * Handles all business logic related to project members, including permission verification, member management, etc.
 */
class ProjectMemberDomainService
{
    public function __construct(
        private readonly ProjectMemberRepositoryInterface $projectMemberRepository,
        private readonly ProjectMemberSettingRepositoryInterface $projectMemberSettingRepository,
    ) {
    }

    /**
     * Update project members - main business method.
     *
     * @param ProjectMemberEntity[] $memberEntities Member entity array
     */
    public function updateProjectMembers(
        string $organizationCode,
        int $projectId,
        array $memberEntities
    ): void {
        // 1. Set project ID and organization code for each member entity
        foreach ($memberEntities as $memberEntity) {
            $memberEntity->setProjectId($projectId);
            $memberEntity->setOrganizationCode($organizationCode);
        }

        // 2. Execute update operation
        Db::transaction(function () use ($projectId, $memberEntities) {
            // First delete all existing members
            $this->projectMemberRepository->deleteByProjectId($projectId, [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value]);

            // Then batch insert new members
            if (! empty($memberEntities)) {
                $this->projectMemberRepository->insert($memberEntities);
            }
        });
    }

    /**
     * Check if user is a user-level member of the project.
     */
    public function isProjectMemberByUser(int $projectId, string $userId): bool
    {
        return $this->projectMemberRepository->existsByProjectAndUser($projectId, $userId);
    }

    /**
     * Check if user is a department-level member of the project.
     */
    public function isProjectMemberByDepartments(int $projectId, array $departmentIds): bool
    {
        return $this->projectMemberRepository->existsByProjectAndDepartments($projectId, $departmentIds);
    }

    /**
     * Get project member list by project ID.
     *
     * @return ProjectMemberEntity[] Project member entity array
     */
    public function getProjectMembers(int $projectId, array $roles = []): array
    {
        return $this->projectMemberRepository->findByProjectId($projectId, $roles);
    }

    /**
     * Delete project members by project ID.
     */
    public function deleteByProjectId(int $projectId): bool
    {
        return (bool) $this->projectMemberRepository->deleteByProjectId($projectId);
    }

    /**
     * Get project ID list and total count by user and departments.
     *
     * @return array ['total' => int, 'list' => array]
     */
    public function getProjectIdsByUserAndDepartmentsWithTotal(
        string $userId,
        array $departmentIds = [],
        ?string $name = null,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = [],
        ?string $joinMethod = null,
        array $organizationCodes = []
    ): array {
        return $this->projectMemberRepository->getProjectIdsByUserAndDepartments(
            $userId,
            $departmentIds,
            $name,
            $sortField,
            $sortDirection,
            $creatorUserIds,
            $joinMethod,
            $organizationCodes
        );
    }

    /**
     * Batch get project member total counts.
     *
     * @return array [project_id => total_count]
     */
    public function getProjectMembersCounts(array $projectIds): array
    {
        return $this->projectMemberRepository->getProjectMembersCounts($projectIds);
    }

    /**
     * Batch get preview of first N members for projects.
     *
     * @return ProjectMemberEntity[][]
     */
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array
    {
        return $this->projectMemberRepository->getProjectMembersPreview($projectIds, $limit);
    }

    /**
     * Get project ID list and total count created by user and has members.
     *
     * @return array ['total' => int, 'list' => array]
     */
    public function getSharedProjectIdsByUserWithTotal(
        string $userId,
        string $organizationCode,
        ?string $name = null,
        int $page = 1,
        int $pageSize = 10,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = []
    ): array {
        return $this->projectMemberRepository->getSharedProjectIdsByUser(
            $userId,
            $organizationCode,
            $name,
            $page,
            $pageSize,
            $sortField,
            $sortDirection,
            $creatorUserIds
        );
    }

    /**
     * Update project pin status.
     */
    public function updateProjectPinStatus(string $userId, int $projectId, string $organizationCode, bool $isPinned): bool
    {
        // 1. Check if data exists, create default data if not exists
        $setting = $this->projectMemberSettingRepository->findByUserAndProject($userId, $projectId);
        if ($setting === null) {
            $this->projectMemberSettingRepository->create($userId, $projectId, $organizationCode);
        }

        // 2. Update pin status
        return $this->projectMemberSettingRepository->updatePinStatus($userId, $projectId, $isPinned);
    }

    /**
     * Get user's pinned project ID list.
     *
     * @return array Pinned project ID array
     */
    public function getUserPinnedProjectIds(string $userId, string $organizationCode): array
    {
        return $this->projectMemberSettingRepository->getPinnedProjectIds($userId, $organizationCode);
    }

    /**
     * Batch get user settings in multiple projects.
     *
     * @return array [project_id => ProjectMemberSettingEntity, ...]
     */
    public function getUserProjectSettings(string $userId, array $projectIds): array
    {
        return $this->projectMemberSettingRepository->findByUserAndProjects($userId, $projectIds);
    }

    /**
     * Update user's last active time in project.
     */
    public function updateUserLastActiveTime(string $userId, int $projectId, string $organizationCode): bool
    {
        // 1. Check if data exists, create default data if not exists
        $setting = $this->projectMemberSettingRepository->findByUserAndProject($userId, $projectId);
        if ($setting === null) {
            $this->projectMemberSettingRepository->create($userId, $projectId, $organizationCode);
        }

        return $this->projectMemberSettingRepository->updateLastActiveTime($userId, $projectId);
    }

    /**
     * Clean up related member settings when deleting project.
     */
    public function cleanupProjectSettings(int $projectId): bool
    {
        $this->projectMemberSettingRepository->deleteByProjectId($projectId);
        return true;
    }

    /**
     * Get creator user ID list of collaboration projects.
     *
     * @param string $userId Current user ID
     * @param array $departmentIds User's department ID array
     * @param string $organizationCode Organization code
     * @return array Creator user ID array
     */
    public function getCollaborationProjectCreatorIds(
        string $userId,
        array $departmentIds,
        string $organizationCode
    ): array {
        return $this->projectMemberRepository->getCollaborationProjectCreatorIds(
            $userId,
            $departmentIds,
            $organizationCode
        );
    }

    /**
     * Set project shortcut.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param int $workspaceId Workspace ID
     * @param string $organizationCode Organization code
     * @return bool Returns true on success
     */
    public function setProjectShortcut(string $userId, int $projectId, int $workspaceId, string $organizationCode): bool
    {
        return $this->projectMemberSettingRepository->setProjectShortcut($userId, $projectId, $workspaceId, $organizationCode);
    }

    /**
     * Cancel project shortcut.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @return bool Returns true on success
     */
    public function cancelProjectShortcut(string $userId, int $projectId): bool
    {
        return $this->projectMemberSettingRepository->cancelProjectShortcut($userId, $projectId);
    }

    /**
     * Check if project shortcut is already set.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param int $workspaceId Workspace ID
     * @return bool Returns true if already set
     */
    public function hasProjectShortcut(string $userId, int $projectId, int $workspaceId): bool
    {
        return $this->projectMemberSettingRepository->hasProjectShortcut($userId, $projectId, $workspaceId);
    }

    /**
     * Get user participated project list (supports collaboration project filtering).
     *
     * @param string $userId User ID
     * @param int $workspaceId Workspace ID (0 means no workspace restriction)
     * @param bool $showCollaboration Whether to show collaboration projects
     * @param null|string $projectName Project name fuzzy search
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $sortField Sort field
     * @param string $sortDirection Sort direction
     * @param null|array $organizationCodes Organization codes
     * @return array ['total' => int, 'list' => array]
     */
    public function getParticipatedProjectsWithCollaboration(
        string $userId,
        int $workspaceId,
        bool $showCollaboration = true,
        ?string $projectName = null,
        int $page = 1,
        int $pageSize = 10,
        ?array $organizationCodes = null,
        string $sortField = 'last_active_at',
        string $sortDirection = 'desc',
    ): array {
        // Determine whether to restrict workspace
        $limitWorkspace = $workspaceId > 0;

        return $this->projectMemberRepository->getParticipatedProjects(
            $userId,
            $limitWorkspace ? $workspaceId : null,
            $showCollaboration,
            $projectName,
            $page,
            $pageSize,
            $sortField,
            $sortDirection,
            $organizationCodes
        );
    }

    /**
     * Initialize project member and settings.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param int $workspaceId Workspace ID
     * @param string $organizationCode Organization code
     */
    public function initializeProjectMemberAndSettings(
        string $userId,
        int $projectId,
        int $workspaceId,
        string $organizationCode
    ): void {
        // Create project member record (set as owner role)
        $memberEntity = new ProjectMemberEntity();
        $memberEntity->setProjectId($projectId);
        $memberEntity->setTargetTypeFromString('User');
        $memberEntity->setTargetId($userId);
        $memberEntity->setRole(MemberRole::OWNER);
        $memberEntity->setOrganizationCode($organizationCode);
        $memberEntity->setInvitedBy($userId);

        // Batch insert member records
        $this->projectMemberRepository->insert([$memberEntity]);

        // Create project member setting record (bind to workspace)
        $this->projectMemberSettingRepository->setProjectShortcut($userId, $projectId, $workspaceId, $organizationCode);
    }

    /**
     * Add project member by invitation link.
     *
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @param MemberRole $role Member role
     * @param string $organizationCode Organization code
     * @param string $invitedBy Inviter ID
     * @return ProjectMemberEntity Created member entity
     */
    public function addMemberByInvitation(
        string $projectId,
        string $userId,
        MemberRole $role,
        string $organizationCode,
        string $invitedBy
    ): ProjectMemberEntity {
        // Check if already a member
        $isExistingMember = $this->getMemberByProjectAndUser((int) $projectId, $userId);
        if ($isExistingMember) {
            return $isExistingMember;
        }

        // Create new project member record
        $memberEntity = new ProjectMemberEntity();
        $memberEntity->setProjectId((int) $projectId);
        $memberEntity->setTargetTypeFromString(MemberType::USER->value);
        $memberEntity->setTargetId($userId);
        $memberEntity->setRole($role);
        $memberEntity->setOrganizationCode($organizationCode);
        $memberEntity->setInvitedBy($invitedBy);
        $memberEntity->setJoinMethod(MemberJoinMethod::LINK);

        // Insert member record
        $this->projectMemberRepository->insert([$memberEntity]);

        return $memberEntity;
    }

    /**
     * Delete project member relationship for specified user.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @return bool Whether deletion succeeded
     */
    public function removeMemberByUser(int $projectId, string $userId): bool
    {
        $deletedCount = $this->projectMemberRepository->deleteByProjectAndUser($projectId, $userId);
        return $deletedCount > 0;
    }

    /**
     * Delete project member relationship for specified user and target type.
     *
     * @param int $projectId Project ID
     * @param string $targetType Target type (User/Department)
     * @param string $targetId Target ID
     * @return bool Whether deletion succeeded
     */
    public function removeMemberByTarget(int $projectId, string $targetType, string $targetId): bool
    {
        $deletedCount = $this->projectMemberRepository->deleteByProjectAndTarget($projectId, $targetType, $targetId);
        return $deletedCount > 0;
    }

    /**
     * Get project member information by project ID and user ID.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @return null|ProjectMemberEntity Project member entity
     */
    public function getMemberByProjectAndUser(int $projectId, string $userId): ?ProjectMemberEntity
    {
        return $this->projectMemberRepository->getMemberByProjectAndUser($projectId, $userId);
    }

    /**
     * Get project member list by project ID and department ID array.
     *
     * @param int $projectId Project ID
     * @param array $departmentIds Department ID array
     * @return ProjectMemberEntity[] Project member entity array
     */
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array
    {
        return $this->projectMemberRepository->getMembersByProjectAndDepartmentIds($projectId, $departmentIds);
    }

    /**
     * Get member list by project ID and member ID array.
     *
     * @param int $projectId Project ID
     * @param array $memberIds Member ID array
     * @return ProjectMemberEntity[] Project member entity array
     */
    public function getMembersByIds(int $projectId, array $memberIds): array
    {
        return $this->projectMemberRepository->getMembersByIds((int) $projectId, $memberIds);
    }

    /**
     * Batch update member permissions (new format: target_type + target_id).
     *
     * @param int $projectId Project ID
     * @param array $roleUpdates [['target_type' => '', 'target_id' => '', 'role' => ''], ...]
     * @return int Number of updated records
     */
    public function batchUpdateRole(int $projectId, array $roleUpdates): int
    {
        $updateData = [];
        foreach ($roleUpdates as $member) {
            $memberRole = MemberRole::validatePermissionLevel($member['role']);
            $updateData[] = [
                'target_type' => $member['target_type'],
                'target_id' => $member['target_id'],
                'role' => $memberRole->value,
            ];
        }

        return $this->projectMemberRepository->batchUpdateRole($projectId, $updateData);
    }

    /**
     * Batch delete members.
     *
     * @param int $projectId Project ID
     * @param array $memberIds Member ID array
     * @return int Number of deleted records
     */
    public function deleteMembersByIds(int $projectId, array $memberIds): int
    {
        return $this->projectMemberRepository->deleteMembersByIds($projectId, $memberIds);
    }

    /**
     * Add project members (internal invitation).
     *
     * @param ProjectMemberEntity[] $memberEntities Member entity array
     * @param string $organizationCode Organization code
     */
    public function addInternalMembers(array $memberEntities, string $organizationCode): void
    {
        if (empty($memberEntities)) {
            return;
        }

        // Set organization code for each member entity
        foreach ($memberEntities as $memberEntity) {
            $memberEntity->setJoinMethod(MemberJoinMethod::INTERNAL);
            $memberEntity->setOrganizationCode($organizationCode);
        }

        // Batch insert members
        $this->projectMemberRepository->insert($memberEntities);
    }

    public function getProjectIdsByCollaboratorTargets(array $targetIds): array
    {
        $roles = [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value];
        return $this->projectMemberRepository->getProjectIdsByCollaboratorTargets($targetIds, $roles);
    }

    /**
     * Batch get user's highest permission role in projects.
     *
     * @param array $projectIds Project ID array
     * @param array $targetIds Target ID array (user IDs and department IDs)
     * @return array [project => role] Project ID mapped to role
     */
    public function getUserHighestRolesInProjects(array $projectIds, array $targetIds): array
    {
        // 1. Get member entity data from Repository
        $memberEntities = $this->projectMemberRepository->getProjectMembersByTargetIds($projectIds, $targetIds);

        if (empty($memberEntities)) {
            return [];
        }

        // 2. Business logic: Group by project, calculate highest permission role for each project
        $projectRoles = [];
        foreach ($memberEntities as $entity) {
            $projectId = $entity->getProjectId();
            $role = $entity->getRole();
            $permissionLevel = $role->getPermissionLevel();

            // If project has no record yet, or current role has higher permission, update
            if (! isset($projectRoles[$projectId]) || $permissionLevel > $projectRoles[$projectId]['level']) {
                $projectRoles[$projectId] = [
                    'role' => $role->value,
                    'level' => $permissionLevel,
                ];
            }
        }

        // 3. Only return role value, not including permission level
        return array_map(fn ($data) => $data['role'], $projectRoles);
    }
}
