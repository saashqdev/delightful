<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\WorkspaceEntity;

interface WorkspaceRepositoryInterface 
{
 /** * Getuser workspace list . */ 
    public function getuser Workspaces(string $userId, int $page, int $pageSize): array; /** * Createworkspace . */ 
    public function createWorkspace(WorkspaceEntity $workspaceEntity): WorkspaceEntity; /** * Updateworkspace . */ 
    public function updateWorkspace(WorkspaceEntity $workspaceEntity): bool; /** * Getworkspace Details. */ 
    public function getWorkspaceById(int $workspaceId): ?WorkspaceEntity; /** * According toIDFindworkspace . */ 
    public function findById(int $workspaceId): ?WorkspaceEntity; /** * ThroughSessionIDGetworkspace . */ 
    public function getWorkspaceByConversationId(string $conversationId): ?WorkspaceEntity; /** * Updateworkspace Status. */ 
    public function updateWorkspaceArchivedStatus(int $workspaceId, int $isArchived): bool; /** * delete workspace . */ 
    public function deleteWorkspace(int $workspaceId): bool; /** * delete workspace Associationtopic . */ 
    public function deleteTopicsByWorkspaceId(int $workspaceId): bool; /** * Updateworkspace current topic . */ 
    public function updateWorkspacecurrent Topic(int $workspaceId, string $topicId): bool; /** * Updateworkspace Status. */ 
    public function updateWorkspaceStatus(int $workspaceId, int $status): bool; /** * According toConditionGetworkspace list * SupportPagingSort. * * @param array $conditions query Condition * @param int $page Page number * @param int $pageSize Per pageQuantity * @param string $orderBy SortField * @param string $orderDirection Sort * @return array [total, list] Totalworkspace list */ 
    public function getWorkspacesByConditions( array $conditions = [], int $page = 1, int $pageSize = 10, string $orderBy = 'id', string $orderDirection = 'asc' ): array; /** * Saveworkspace Createor Update. * * @param WorkspaceEntity $workspaceEntity workspace * @return WorkspaceEntity Saveworkspace */ 
    public function save(WorkspaceEntity $workspaceEntity): WorkspaceEntity; /** * GetAllworkspace OrganizationCodelist . * * @return array OrganizationCodelist */ 
    public function getUniqueOrganizationCodes(): array; /** * BatchGetworkspace NameMap. * * @param array $workspaceIds workspace IDArray * @return array ['workspace_id' => 'workspace_name'] KeyValuePair */ 
    public function getWorkspaceNamesBatch(array $workspaceIds): array; 
}
 
