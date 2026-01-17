<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberEntity;

/**
 * Project member repository interface.
 *
 * Provides persistence operations for project member data
 */
interface ProjectMemberRepositoryInterface
{
    /**
     * Batch insert project members.
     *
     * @param ProjectMemberEntity[] $projectMemberEntities Array of project member entities
     */
    public function insert(array $projectMemberEntities): void;

    /**
     * Delete all members by project ID.
     *
     * @param int $projectId Project ID
     * @param array $roles Roles
     * @return int Number of deleted records
     */
    public function deleteByProjectId(int $projectId, array $roles = []): int;

    /**
     * Batch delete members by ID array.
     *
     * @param array $ids Array of member IDs
     * @return int Number of deleted records
     */
    public function deleteByIds(array $ids): int;

    /**
     * Delete member relationship for specified project and user.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @return int Number of deleted records
     */
    public function deleteByProjectAndUser(int $projectId, string $userId): int;

    /**
     * Delete member relationship for specified project and target.
     *
     * @param int $projectId Project ID
     * @param string $targetType Target type
     * @param string $targetId Target ID
     * @return int Number of deleted records
     */
    public function deleteByProjectAndTarget(int $projectId, string $targetType, string $targetId): int;

    /**
     * Check if member relationship exists for project and user.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @return bool Returns true if exists, otherwise false
     */
    public function existsByProjectAndUser(int $projectId, string $userId): bool;

    /**
     * Check if member relationship exists for project and department list.
     *
     * @param int $projectId Project ID
     * @param array $departmentIds Array of department IDs
     * @return bool Returns true if exists, otherwise false
     */
    public function existsByProjectAndDepartments(int $projectId, array $departmentIds): bool;

    /**
     * Get all project members by project ID.
     *
     * @param int $projectId Project ID
     * @param array $roles Roles
     * @return ProjectMemberEntity[] Array of project member entities
     */
    public function findByProjectId(int $projectId, array $roles = []): array;

    /**
     * Get project ID list and total count by user and departments.
     *
     * @param string $userId User ID
     * @param array $departmentIds Array of department IDs
     * @param null|string $name Fuzzy search keyword for project name
     * @param null|string $sortField Sort field: updated_at, created_at, last_active_at
     * @param array $organizationCodes Organization code list (for filtering)
     * @return array ['total' => int, 'list' => array]
     */
    public function getProjectIdsByUserAndDepartments(
        string $userId,
        array $departmentIds = [],
        ?string $name = null,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = [],
        ?string $joinMethod = null,
        array $organizationCodes = []
    ): array;

    /**
     * Batch get project member counts.
     *
     * @param array $projectIds Array of project IDs
     * @return array [project_id => total_count]
     */
    public function getProjectMembersCounts(array $projectIds): array;

    /**
     * Batch get top N members preview for projects.
     *
     * @param array $projectIds Array of project IDs
     * @param int $limit Limit count, default 4
     * @return array [project_id => [['target_type' => '', 'target_id' => ''], ...]]
     */
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array;

    /**
     * Get project ID list and total count created by user with members.
     *
     * @return array ['total' => int, 'list' => array]
     */
    public function getSharedProjectIdsByUser(
        string $userId,
        string $organizationCode,
        ?string $name = null,
        int $page = 1,
        int $pageSize = 10,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = []
    ): array;

    /**
     * Get creator user ID list for collaboration projects.
     *
     * @param string $userId Current user ID
     * @param array $departmentIds Array of user's department IDs
     * @param string $organizationCode Organization code
     * @return array Array of creator user IDs
     */
    public function getCollaborationProjectCreatorIds(
        string $userId,
        array $departmentIds,
        string $organizationCode
    ): array;

    /**
     * Get list of projects user participated in (supports collaboration project filtering and workspace binding filtering).
     *
     * @param string $userId User ID
     * @param int $workspaceId Workspace ID
     * @param bool $showCollaboration Whether to show collaboration projects
     * @param null|string $projectName Fuzzy search for project name
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $sortField Sort field
     * @param string $sortDirection Sort direction
     * @param null|array $organizationCodes Organization codes
     * @return array ['total' => int, 'list' => array]
     */
    public function getParticipatedProjects(
        string $userId,
        ?int $workspaceId,
        bool $showCollaboration = true,
        ?string $projectName = null,
        int $page = 1,
        int $pageSize = 10,
        string $sortField = 'last_active_at',
        string $sortDirection = 'desc',
        ?array $organizationCodes = null
    ): array;

    /**
     * Get project member information by project ID and user ID.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @return null|ProjectMemberEntity Project member entity
     */
    public function getMemberByProjectAndUser(int $projectId, string $userId): ?ProjectMemberEntity;

    /**
     * Get member list by project ID and member ID array.
     *
     * @param int $projectId Project ID
     * @param array $memberIds Array of member IDs
     * @return ProjectMemberEntity[] Array of project member entities
     */
    public function getMembersByIds(int $projectId, array $memberIds): array;

    /**
     * Get project member list by project ID and department ID array.
     *
     * @param int $projectId Project ID
     * @param array $departmentIds Array of department IDs
     * @return ProjectMemberEntity[] Array of project member entities
     */
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array;

    /**
     * Batch update member permissions (new format: target_type + target_id).
     *
     * @param int $projectId Project ID
     * @param array $roleUpdates [['target_type' => '', 'target_id' => '', 'role' => ''], ...]
     * @return int Number of updated records
     */
    public function batchUpdateRole(int $projectId, array $roleUpdates): int;

    /**
     * Batch delete members (soft delete).
     *
     * @param int $projectId Project ID
     * @param array $memberIds Array of member IDs
     * @return int Number of deleted records
     */
    public function deleteMembersByIds(int $projectId, array $memberIds): int;

    /**
     * Get project ID list by collaborator target IDs (excluding OWNER role).
     *
     * @param array $targetIds Array of target IDs (user IDs or department IDs)
     * @return array Project IDs
     */
    public function getProjectIdsByCollaboratorTargets(array $targetIds, array $roles): array;

    /**
     * Batch get user's member records in projects.
     *
     * @param array $projectIds Array of project IDs
     * @param array $targetIds Array of target IDs (user IDs and department IDs)
     * @return ProjectMemberEntity[] Array of member entities
     */
    public function getProjectMembersByTargetIds(array $projectIds, array $targetIds): array;
}
