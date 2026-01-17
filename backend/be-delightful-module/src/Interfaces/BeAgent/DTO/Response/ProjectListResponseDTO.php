<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

/**
 * 项目列表响应DTO.
 */
class ProjectListResponseDTO
{
    public function __construct(
        public readonly array $list,
        public readonly int $total
    ) {
    }

    public static function fromResult(array $result, array $workspaceNameMap = [], array $projectIdsWithMember = [], array $projectStatusMap = []): self
    {
        $projects = $result['list'] ?? $result;
        $total = $result['total'] ?? count($projects);

        $list = array_map(function ($project) use ($workspaceNameMap, $projectIdsWithMember, $projectStatusMap) {
            $workspaceName = $workspaceNameMap[$project->getWorkspaceId()] ?? null;
            $hasProjectMember = in_array($project->getId(), $projectIdsWithMember);
            $projectStatus = $projectStatusMap[$project->getId()] ?? null;
            return ProjectItemDTO::fromEntity($project, $projectStatus, $workspaceName, $hasProjectMember)->toArray();
        }, $projects);

        return new self(
            list: $list,
            total: $total,
        );
    }

    public function toArray(): array
    {
        return [
            'list' => $this->list,
            'total' => $this->total,
        ];
    }
}
