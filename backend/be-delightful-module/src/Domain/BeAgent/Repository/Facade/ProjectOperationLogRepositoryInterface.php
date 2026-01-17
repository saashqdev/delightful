<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectOperationLogEntity;

/**
 * Project operation log repository interface.
 */
interface ProjectOperationLogRepositoryInterface
{
    /**
     * Save operation log.
     */
    public function save(ProjectOperationLogEntity $operationLog): ProjectOperationLogEntity;

    /**
     * Find operation log list by project ID.
     *
     * @param int $projectId Project ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Log list
     */
    public function findByProjectId(int $projectId, int $page = 1, int $pageSize = 20): array;

    /**
     * Find operation logs by project and user.
     *
     * @param int $projectId Project ID
     * @param string $userId User ID
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Log list
     */
    public function findByProjectAndUser(int $projectId, string $userId, int $page = 1, int $pageSize = 20): array;

    /**
     * Find logs by project and action type.
     *
     * @param int $projectId Project ID
     * @param string $action Action type
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Log list
     */
    public function findByProjectAndAction(int $projectId, string $action, int $page = 1, int $pageSize = 20): array;

    /**
     * Count operation logs by project ID.
     *
     * @param int $projectId Project ID
     * @return int Log count
     */
    public function countByProjectId(int $projectId): int;

    /**
     * Find operation logs by organization code.
     *
     * @param string $organizationCode Organization code
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Log list
     */
    public function findByOrganization(string $organizationCode, int $page = 1, int $pageSize = 20): array;
}
