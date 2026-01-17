<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectOperationLogEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectOperationLogRepositoryInterface;

/**
 * Project Operation Log Domain Service
 */
class ProjectOperationLogDomainService
{
    public function __construct(
        private readonly ProjectOperationLogRepositoryInterface $projectOperationLogRepository
    ) {
    }

    /**
     * Save operation log.
     */
    public function saveOperationLog(ProjectOperationLogEntity $operationLog): ProjectOperationLogEntity
    {
        return $this->projectOperationLogRepository->save($operationLog);
    }

    /**
     * Get project operation log list.
     */
    public function getProjectOperationLogs(int $projectId, int $page = 1, int $pageSize = 20): array
    {
        return $this->projectOperationLogRepository->findByProjectId($projectId, $page, $pageSize);
    }

    /**
     * Get operation logs by project and user.
     */
    public function getProjectUserOperationLogs(int $projectId, string $userId, int $page = 1, int $pageSize = 20): array
    {
        return $this->projectOperationLogRepository->findByProjectAndUser($projectId, $userId, $page, $pageSize);
    }

    /**
     * Get logs by project and action type.
     * @return ProjectOperationLogEntity[]
     */
    public function getProjectActionOperationLogs(int $projectId, string $action, int $page = 1, int $pageSize = 20): array
    {
        return $this->projectOperationLogRepository->findByProjectAndAction($projectId, $action, $page, $pageSize);
    }

    /**
     * Count project operation logs.
     */
    public function countProjectOperationLogs(int $projectId): int
    {
        return $this->projectOperationLogRepository->countByProjectId($projectId);
    }

    /**
     * Get operation logs by organization code.
     */
    public function getOrganizationOperationLogs(string $organizationCode, int $page = 1, int $pageSize = 20): array
    {
        return $this->projectOperationLogRepository->findByOrganization($organizationCode, $page, $pageSize);
    }
}
