<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;

/**
 * Project repository interface.
 */
interface ProjectRepositoryInterface
{
    /**
     * Find project by ID.
     */
    public function findById(int $id): ?ProjectEntity;

    /**
     * Save project.
     */
    public function save(ProjectEntity $project): ProjectEntity;

    public function create(ProjectEntity $project): ProjectEntity;

    /**
     * Delete project (soft delete).
     */
    public function delete(ProjectEntity $project): bool;

    /**
     * Batch get project information.
     */
    public function findByIds(array $ids): array;

    /**
     * Get project list by conditions
     * Supports pagination and sorting.
     */
    public function getProjectsByConditions(
        array $conditions = [],
        int $page = 1,
        int $pageSize = 10,
        string $orderBy = 'updated_at',
        string $orderDirection = 'desc'
    ): array;

    public function updateProjectByCondition(array $condition, array $data): bool;

    /**
     * Update project's updated_at to current time.
     */
    public function updateUpdatedAtToNow(int $projectId): bool;

    /**
     * Get project ID list by workspace ID.
     *
     * @param int $workspaceId Workspace ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return array Project ID list
     */
    public function getProjectIdsByWorkspaceId(int $workspaceId, string $userId, string $organizationCode): array;

    /**
     * Batch get project names by IDs.
     *
     * @param array $projectIds Project ID array
     * @return array ['project_id' => 'project_name'] key-value pairs
     */
    public function getProjectNamesBatch(array $projectIds): array;

    public function getOrganizationCodesByProjectIds(array $projectIds): array;
}
