<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectOperationLogEntity;
/** * ItemLogRepository interface. */

interface ProjectOperationLogRepositoryInterface 
{
 /** * SaveLog. */ 
    public function save(ProjectOperationLogEntity $operationLog): ProjectOperationLogEntity; /** * According toProject IDFindLoglist . * * @param int $projectId Project ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array Loglist */ 
    public function findByProjectId(int $projectId, int $page = 1, int $pageSize = 20): array; /** * According toItemuser FindLog. * * @param int $projectId Project ID * @param string $userId user ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array Loglist */ 
    public function findByProjectAnduser (int $projectId, string $userId, int $page = 1, int $pageSize = 20): array; /** * According toItemTypeFindLog. * * @param int $projectId Project ID * @param string $action Type * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array Loglist */ 
    public function findByProjectAndAction(int $projectId, string $action, int $page = 1, int $pageSize = 20): array; /** * According toProject IDCountLogQuantity. * * @param int $projectId Project ID * @return int LogQuantity */ 
    public function countByProjectId(int $projectId): int; /** * According toorganization code FindLog. * * @param string $organizationCode organization code * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array Loglist */ 
    public function findByOrganization(string $organizationCode, int $page = 1, int $pageSize = 20): array; 
}
 
