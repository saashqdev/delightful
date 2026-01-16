<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * Itemlist ResponseDTO. */

class ParticipatedProjectlist ResponseDTO 
{
 
    public function __construct( 
    public readonly array $list, 
    public readonly int $total ) 
{
 
}
 /** * Fromquery ResultCreateResponseDTO. * * @param array $result query Resultincluding list total * @param array $workspaceNameMap workspace NameMap * @param array $projectIdsWithMember HaveMemberProject IDArray */ 
    public 
    static function fromResult( array $result, array $workspaceNameMap = [], array $projectIdsWithMember = [] ): self 
{
 $projects = $result['list'] ?? $result; $total = $result['total'] ?? count($projects); $list = array_map(function ($projectData) use ($workspaceNameMap, $projectIdsWithMember) 
{
 $workspaceName = $workspaceNameMap[$projectData['workspace_id']] ?? null;
$hasProjectMember = in_array($projectData['id'], $projectIdsWithMember); return ParticipatedProjectItemDTO::fromArray($projectData, $workspaceName, $hasProjectMember)->toArray(); 
}
, $projects); return new self( list: $list, total: $total, ); 
}
 
    public function toArray(): array 
{
 return [ 'list' => $this->list, 'total' => $this->total, ]; 
}
 
}
 
