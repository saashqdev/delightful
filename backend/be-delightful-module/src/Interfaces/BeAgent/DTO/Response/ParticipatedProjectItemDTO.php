<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * ItemDTO (ExtensionProjectItemDTOAddItemHaveField). */

class ParticipatedProjectItemDTO extends ProjectItemDTO 
{
 
    public function __construct( // InheritanceClassAllField string $id, string $workspaceId, string $projectName, string $projectDescription, string $workDir, string $currentTopicId, string $currentTopicStatus, string $projectStatus, ?string $projectMode, ?string $workspaceName, ?string $createdAt, ?string $updatedAt, ?string $tag, ?string $userId, ?bool $isCollaborationEnabled, ?string $defaultJoinpermission , // ItemHaveField 
    public readonly string $userRole = 'owner', // user AtItemin Roleowner-Itemowner collaborator-Author 
    public readonly bool $isPinned = false, 
    public readonly string $organizationCode = '', ) 
{
 parent::__construct( $id, $workspaceId, $projectName, $projectDescription, $workDir, $currentTopicId, $currentTopicStatus, $projectStatus, $projectMode, $workspaceName, $createdAt, $updatedAt, $tag, $userId, $isCollaborationEnabled, $defaultJoinpermission ); 
}
 /** * Create DTO from array data. */ 
    public 
    static function fromArray(array $data, ?string $workspaceName = null, bool $hasProjectMember = false): self 
{
 $isCollaborator = (bool) ($data['is_collaborator'] ?? false); // Tag logic: DetermineItemwhether whether HaveAuthor $tag = $hasProjectMember ? 'collaboration' : ''; // Role logic: current user AtItemin Role $userRole = $data['user_role'] ?? ''; return new self( id: (string) $data['id'], workspaceId: (string) $data['workspace_id'], projectName: $data['project_name'] ?? '', projectDescription: $data['project_description'] ?? '', workDir: $data['work_dir'] ?? '', currentTopicId: (string) ($data['current_topic_id'] ?? ''), currentTopicStatus: self::convertStatus($data['current_topic_status'] ?? ''), projectStatus: self::convertStatus($data['project_status'] ?? ''), projectMode: $data['project_mode'] ?? 'default', workspaceName: $workspaceName, createdAt: $data['created_at'] ?? null, updatedAt: $data['updated_at'] ?? null, tag: $tag, userId: $data['user_id'] ?? '', userRole: $userRole, isPinned: (bool) ($data['is_pinned'] ?? false), organizationCode: $data['organization_code'] ?? '', isCollaborationEnabled: (bool) ($data['is_collaboration_enabled'] ?? false), defaultJoinpermission : $data['default_join_permission'] ?? '', ); 
}
 /** * Convert toArray (including ItemHaveField). */ 
    public function toArray(): array 
{
 return array_merge(parent::toArray(), [ 'user_role' => $this->userRole, 'is_pinned' => $this->isPinned, 'organization_code' => $this->organizationCode, ]); 
}
 /** * Convert status value to string. */ 
    private 
    static function convertStatus(mixed $status): string 
{
 return (string) $status; 
}
 
}
 
