<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectOperationLogEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectOperationLogRepositoryInterface;
/** * ItemLogService */

class ProjectOperationLogDomainService 
{
 
    public function __construct( 
    private readonly ProjectOperationLogRepositoryInterface $projectOperationLogRepository ) 
{
 
}
 /** * SaveLog. */ 
    public function saveOperationLog(ProjectOperationLogEntity $operationLog): ProjectOperationLogEntity 
{
 return $this->projectOperationLogRepository->save($operationLog); 
}
 /** * GetItemLoglist . */ 
    public function getProjectOperationLogs(int $projectId, int $page = 1, int $pageSize = 20): array 
{
 return $this->projectOperationLogRepository->findByProjectId($projectId, $page, $pageSize); 
}
 /** * According toItemuser GetLog. */ 
    public function getProjectuser OperationLogs(int $projectId, string $userId, int $page = 1, int $pageSize = 20): array 
{
 return $this->projectOperationLogRepository->findByProjectAnduser ($projectId, $userId, $page, $pageSize); 
}
 /** * According toItemTypeGetLog. * @return ProjectOperationLogEntity[] */ 
    public function getProjectActionOperationLogs(int $projectId, string $action, int $page = 1, int $pageSize = 20): array 
{
 return $this->projectOperationLogRepository->findByProjectAndAction($projectId, $action, $page, $pageSize); 
}
 /** * CountItemLogQuantity. */ 
    public function countProjectOperationLogs(int $projectId): int 
{
 return $this->projectOperationLogRepository->countByProjectId($projectId); 
}
 /** * According toorganization code GetLog. */ 
    public function getOrganizationOperationLogs(string $organizationCode, int $page = 1, int $pageSize = 20): array 
{
 return $this->projectOperationLogRepository->findByOrganization($organizationCode, $page, $pageSize); 
}
 
}
 
