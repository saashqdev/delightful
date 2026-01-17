<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

/**
 * Collaboration project list response DTO.
 */
class CollaborationProjectListResponseDTO
{
    public function __construct(
        public readonly array $list,
        public readonly int $total
    ) {
    }

    /**
     * Create response DTO from project data.
     *
     * @param int $total Total count
     * @param array $userRolesMap User role mapping [project => role]
     */
    public static function fromProjectData(
        array $projects,
        array $collaborationProjects,
        array $creatorInfoMap = [],
        array $collaboratorsInfoMap = [],
        array $workspaceNameMap = [],
        int $total = 0,
        array $userRolesMap = []
    ): self {
        $projectIdMapEntities = [];
        foreach ($projects as $project) {
            $projectIdMapEntities[$project->getId()] = $project;
        }

        $list = array_map(function ($collaborationProject) use ($creatorInfoMap, $collaboratorsInfoMap, $workspaceNameMap, $projectIdMapEntities, $userRolesMap) {
            $projectId = $collaborationProject['project_id'];
            $projectEntity = $projectIdMapEntities[$projectId] ?? null;
            if (! $projectEntity) {
                return [];
            }

            $workspaceName = $workspaceNameMap[$projectEntity->getWorkspaceId()] ?? null;
            $creator = $creatorInfoMap[$projectEntity->getUserId()] ?? null;
            $collaboratorsInfo = $collaboratorsInfoMap[$projectId] ?? ['members' => [], 'member_count' => 0];
            $isPinned = (bool) ($collaborationProject['is_pinned'] ?? false);
            $lastActiveAt = $collaborationProject['last_active_at'] ?? null;
            $isBindWorkspace = (bool) ($collaborationProject['is_bind_workspace'] ?? false);
            $bindWorkspaceId = (string) ($collaborationProject['bind_workspace_id'] ?? '');
            $userRole = $userRolesMap[$projectId] ?? null;

            return CollaborationProjectItemDTO::fromEntityWithExtendedInfo(
                $projectEntity,
                $creator,
                $collaboratorsInfo['members'],
                $collaboratorsInfo['member_count'],
                null,
                $workspaceName,
                $isPinned,
                $lastActiveAt,
                $isBindWorkspace,
                $bindWorkspaceId,
                $userRole
            )->toArray();
        }, $collaborationProjects);

        return new self(
            list: $list,
            total: $total ?: count($projects),
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'list' => $this->list,
            'total' => $this->total,
        ];
    }
}
