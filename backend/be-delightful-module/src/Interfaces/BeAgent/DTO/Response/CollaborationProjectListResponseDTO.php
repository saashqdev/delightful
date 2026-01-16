<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * collaboration Itemlist ResponseDTO. */

class CollaborationProjectlist ResponseDTO 
{
 
    public function __construct( 
    public readonly array $list, 
    public readonly int $total ) 
{
 
}
 /** * FromItemDataCreateResponseDTO. * * @param int $total Total * @param array $userRolesMap user RoleMap [project => role] */ 
    public 
    static function fromProjectData( array $projects, array $collaborationProjects, array $creatorinfo Map = [], array $collaboratorsinfo Map = [], array $workspaceNameMap = [], int $total = 0, array $userRolesMap = [] ): self 
{
 $projectIdMapEntities = []; foreach ($projects as $project) 
{
 $projectIdMapEntities[$project->getId()] = $project; 
}
 $list = array_map(function ($collaborationProject) use ($creatorinfo Map, $collaboratorsinfo Map, $workspaceNameMap, $projectIdMapEntities, $userRolesMap) 
{
 $projectId = $collaborationProject['project_id'];
$projectEntity = $projectIdMapEntities[$projectId] ?? null; if (! $projectEntity) 
{
 return []; 
}
 $workspaceName = $workspaceNameMap[$projectEntity->getWorkspaceId()] ?? null; $creator = $creatorinfo Map[$projectEntity->getuser Id()] ?? null; $collaboratorsinfo = $collaboratorsinfo Map[$projectId] ?? ['members' => [], 'member_count' => 0]; $isPinned = (bool) ($collaborationProject['is_pinned'] ?? false); $lastActiveAt = $collaborationProject['last_active_at'] ?? null; $isBindWorkspace = (bool) ($collaborationProject['is_bind_workspace'] ?? false); $bindWorkspaceId = (string) ($collaborationProject['bind_workspace_id'] ?? ''); $userRole = $userRolesMap[$projectId] ?? null; return CollaborationProjectItemDTO::fromEntityWithExtendedinfo ( $projectEntity, $creator, $collaboratorsinfo ['members'], $collaboratorsinfo ['member_count'], null, $workspaceName, $isPinned, $lastActiveAt, $isBindWorkspace, $bindWorkspaceId, $userRole )->toArray(); 
}
, $collaborationProjects); return new self( list: $list, total: $total ?: count($projects), ); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'list' => $this->list, 'total' => $this->total, ]; 
}
 
}
 
