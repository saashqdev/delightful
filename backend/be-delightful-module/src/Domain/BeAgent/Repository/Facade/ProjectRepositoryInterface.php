<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
/** * ItemRepository interface. */

interface ProjectRepositoryInterface 
{
 /** * According toIDFindItem. */ 
    public function findById(int $id): ?ProjectEntity; /** * SaveItem. */ 
    public function save(ProjectEntity $project): ProjectEntity; 
    public function create(ProjectEntity $project): ProjectEntity; /** * delete Itemdelete . */ 
    public function delete(ProjectEntity $project): bool; /** * BatchGetIteminfo . */ 
    public function findByIds(array $ids): array; /** * According toConditionGetItemlist * SupportPagingSort. */ 
    public function getProjectsByConditions( array $conditions = [], int $page = 1, int $pageSize = 10, string $orderBy = 'updated_at', string $orderDirection = 'desc' ): array; 
    public function updateProjectByCondition(array $condition, array $data): bool; /** * UpdateItemupdated_atas current Time. */ 
    public function updateUpdatedAtToNow(int $projectId): bool; /** * According toworkspace IDGetProject IDlist . * * @param int $workspaceId workspace ID * @param string $userId user ID * @param string $organizationCode OrganizationCode * @return array Project IDlist */ 
    public function getProjectIdsByWorkspaceId(int $workspaceId, string $userId, string $organizationCode): array; /** * Batch get project names by IDs. * * @param array $projectIds Project ID array * @return array ['project_id' => 'project_name'] key-value pairs */ 
    public function getProjectNamesBatch(array $projectIds): array; 
    public function getOrganizationCodesByProjectIds(array $projectIds): array; 
}
 
