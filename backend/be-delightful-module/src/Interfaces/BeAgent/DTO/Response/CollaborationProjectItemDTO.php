<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;

/**
 * Collaboration project item DTO (extends ProjectItemDTO).
 */
class CollaborationProjectItemDTO extends ProjectItemDTO
{
    public function __construct(
        // Inherit all fields from parent class
        string $id,
        string $workspaceId,
        string $projectName,
        string $projectDescription,
        string $workDir,
        string $currentTopicId,
        string $currentTopicStatus,
        string $projectStatus,
        ?string $projectMode,
        ?string $workspaceName,
        ?string $createdAt,
        ?string $updatedAt,
        ?string $tag,
        ?string $userId,
        ?bool $isCollaborationEnabled,
        ?string $defaultJoinPermission,

        // New fields
        public readonly ?CreatorInfoDTO $creator,
        public readonly array $members,
        public readonly int $memberCount,
        public readonly bool $isPinned = false,
        public readonly ?string $lastActiveAt = null,
        public readonly bool $isBindWorkspace = false,
        public readonly string $bindWorkspaceId = '',
        public readonly ?string $userRole = null,
    ) {
        parent::__construct(
            $id,
            $workspaceId,
            $projectName,
            $projectDescription,
            $workDir,
            $currentTopicId,
            $currentTopicStatus,
            $projectStatus,
            $projectMode,
            $workspaceName,
            $createdAt,
            $updatedAt,
            $tag,
            $userId,
            $isCollaborationEnabled,
            $defaultJoinPermission,
        );
    }

    /**
     * Create DTO from project entity and extended information.
     */
    public static function fromEntityWithExtendedInfo(
        ProjectEntity $project,
        ?CreatorInfoDTO $creator = null,
        array $members = [],
        int $memberCount = 0,
        ?string $projectStatus = null,
        ?string $workspaceName = null,
        bool $isPinned = false,
        ?string $lastActiveAt = null,
        bool $isBindWorkspace = false,
        string $bindWorkspaceId = '',
        ?string $userRole = null
    ): self {
        return new self(
            id: (string) $project->getId(),
            workspaceId: (string) $project->getWorkspaceId(),
            projectName: $project->getProjectName(),
            projectDescription: $project->getProjectDescription(),
            workDir: $project->getWorkDir(),
            currentTopicId: (string) $project->getCurrentTopicId(),
            currentTopicStatus: $project->getCurrentTopicStatus(),
            projectStatus: $projectStatus ?? $project->getCurrentTopicStatus(),
            projectMode: $project->getProjectMode(),
            workspaceName: $workspaceName,
            createdAt: $project->getCreatedAt(),
            updatedAt: $project->getUpdatedAt(),
            creator: $creator,
            members: $members,
            memberCount: $memberCount,
            isPinned: $isPinned,
            lastActiveAt: $lastActiveAt,
            isBindWorkspace: $isBindWorkspace,
            bindWorkspaceId: $bindWorkspaceId,
            userRole: $userRole,
            tag: 'collaboration',
            userId: $project->getUserId(),
            isCollaborationEnabled: $project->getIsCollaborationEnabled(),
            defaultJoinPermission: $project->getDefaultJoinPermission()->value,
        );
    }

    /**
     * Convert to array (including extended fields).
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'tag' => $this->tag,
            'creator' => $this->creator?->toArray(),
            'members' => array_map(fn ($member) => $member->toArray(), $this->members),
            'member_count' => $this->memberCount,
            'is_pinned' => $this->isPinned,
            'last_active_at' => $this->lastActiveAt,
            'is_bind_workspace' => $this->isBindWorkspace,
            'bind_workspace_id' => $this->bindWorkspaceId,
            'user_role' => $this->userRole,
        ]);
    }
}
