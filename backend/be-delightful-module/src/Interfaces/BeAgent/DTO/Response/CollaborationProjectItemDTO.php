<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
/** * collaboration ItemDTO (ExtensionProjectItemDTO). */

class CollaborationProjectItemDTO extends ProjectItemDTO 
{
 
    public function __construct( // InheritanceClassAllField string $id, string $workspaceId, string $projectName, string $projectDescription, string $workDir, string $currentTopicId, string $currentTopicStatus, string $projectStatus, ?string $projectMode, ?string $workspaceName, ?string $createdAt, ?string $updatedAt, ?string $tag, ?string $userId, ?bool $isCollaborationEnabled, ?string $defaultJoinpermission , // NewField 
    public readonly ?creator info DTO $creator, 
    public readonly array $members, 
    public readonly int $memberCount, 
    public readonly bool $isPinned = false, 
    public readonly ?string $lastActiveAt = null, 
    public readonly bool $isBindWorkspace = false, 
    public readonly string $bindWorkspaceId = '', 
    public readonly ?string $userRole = null, ) 
{
 parent::__construct( $id, $workspaceId, $projectName, $projectDescription, $workDir, $currentTopicId, $currentTopicStatus, $projectStatus, $projectMode, $workspaceName, $createdAt, $updatedAt, $tag, $userId, $isCollaborationEnabled, $defaultJoinpermission , ); 
}
 /** * FromItemExtensioninfo CreateDTO. */ 
    public 
    static function fromEntityWithExtendedinfo ( ProjectEntity $project, ?creator info DTO $creator = null, array $members = [], int $memberCount = 0, ?string $projectStatus = null, ?string $workspaceName = null, bool $isPinned = false, ?string $lastActiveAt = null, bool $isBindWorkspace = false, string $bindWorkspaceId = '', ?string $userRole = null ): self 
{
 return new self( id: (string) $project->getId(), workspaceId: (string) $project->getWorkspaceId(), projectName: $project->getProjectName(), projectDescription: $project->getProjectDescription(), workDir: $project->getWorkDir(), currentTopicId: (string) $project->getcurrent TopicId(), currentTopicStatus: $project->getcurrent TopicStatus(), projectStatus: $projectStatus ?? $project->getcurrent TopicStatus(), projectMode: $project->getProjectMode(), workspaceName: $workspaceName, createdAt: $project->getCreatedAt(), updatedAt: $project->getUpdatedAt(), creator: $creator, members: $members, memberCount: $memberCount, isPinned: $isPinned, lastActiveAt: $lastActiveAt, isBindWorkspace: $isBindWorkspace, bindWorkspaceId: $bindWorkspaceId, userRole: $userRole, tag: 'collaboration', userId: $project->getuser Id(), isCollaborationEnabled: $project->getIsCollaborationEnabled(), defaultJoinpermission : $project->getDefaultJoinpermission ()->value, ); 
}
 /** * Convert toArray (including ExtensionField). */ 
    public function toArray(): array 
{
 return array_merge(parent::toArray(), [ 'tag' => $this->tag, 'creator' => $this->creator?->toArray(), 'members' => array_map(fn ($member) => $member->toArray(), $this->members), 'member_count' => $this->memberCount, 'is_pinned' => $this->isPinned, 'last_active_at' => $this->lastActiveAt, 'is_bind_workspace' => $this->isBindWorkspace, 'bind_workspace_id' => $this->bindWorkspaceId, 'user_role' => $this->userRole, ]); 
}
 
}
 
