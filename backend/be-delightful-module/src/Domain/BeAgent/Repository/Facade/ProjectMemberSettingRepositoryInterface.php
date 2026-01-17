<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberSettingEntity;

/**
 * Project member setting repository interface.
 *
 * Provides persistence operations for project member setting data
 */
interface ProjectMemberSettingRepositoryInterface
{
    /**
     * Find setting by user ID and project ID.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @return null|ProjectMemberSettingEntity Setting entity or null
     */
    public function findByUserAndProject(string $userId, int $projectId): ?ProjectMemberSettingEntity;

    /**
     * Create project member setting.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param string $organizationCode Organization code
     * @return ProjectMemberSettingEntity Created setting entity
     */
    public function create(string $userId, int $projectId, string $organizationCode): ProjectMemberSettingEntity;

    /**
     * Create or update project member setting.
     *
     * @param ProjectMemberSettingEntity $entity Setting entity
     * @return ProjectMemberSettingEntity Saved entity
     */
    public function save(ProjectMemberSettingEntity $entity): ProjectMemberSettingEntity;

    /**
     * Update pin status (assuming record already exists).
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param bool $isPinned Whether pinned
     * @return bool Returns true on successful update
     */
    public function updatePinStatus(string $userId, int $projectId, bool $isPinned): bool;

    /**
     * Batch get user's pinned project ID list.
     *
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return array Array of pinned project IDs
     */
    public function getPinnedProjectIds(string $userId, string $organizationCode): array;

    /**
     * Batch get user's settings for multiple projects.
     *
     * @param string $userId User ID
     * @param array $projectIds Array of project IDs
     * @return array [project_id => ProjectMemberSettingEntity, ...]
     */
    public function findByUserAndProjects(string $userId, array $projectIds): array;

    /**
     * Update last active time.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @return bool Returns true on successful update
     */
    public function updateLastActiveTime(string $userId, int $projectId): bool;

    /**
     * Delete all settings related to project.
     *
     * @param int $projectId Project ID
     * @return int Number of deleted records
     */
    public function deleteByProjectId(int $projectId): int;

    /**
     * Delete all settings related to user.
     *
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return int Number of deleted records
     */
    public function deleteByUser(string $userId, string $organizationCode): int;

    /**
     * Set project shortcut (bind to workspace).
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param int $workspaceId Workspace ID
     * @param string $organizationCode Organization code
     * @return bool Returns true on successful setting
     */
    public function setProjectShortcut(string $userId, int $projectId, int $workspaceId, string $organizationCode): bool;

    /**
     * Cancel project shortcut (cancel workspace binding).
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @return bool Returns true on successful cancellation
     */
    public function cancelProjectShortcut(string $userId, int $projectId): bool;

    /**
     * Check if project has shortcut set.
     *
     * @param string $userId User ID
     * @param int $projectId Project ID
     * @param int $workspaceId Workspace ID
     * @return bool Returns true if set
     */
    public function hasProjectShortcut(string $userId, int $projectId, int $workspaceId): bool;
}
