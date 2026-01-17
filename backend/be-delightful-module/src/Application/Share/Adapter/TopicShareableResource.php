<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\Adapter;

use Delightful\BeDelightful\Application\Share\DTO\ShareableResourceDTO;
use Delightful\BeDelightful\Application\Share\Factory\Facade\ResourceFactoryInterface;
use Delightful\BeDelightful\Application\BeAgent\Service\WorkspaceAppService;
use Exception;

class TopicShareableResource implements ResourceFactoryInterface
{
    public function __construct(private readonly WorkspaceAppService $workspaceAppService)
    {
    }

    public function createResource(string $resourceId, string $userId, string $organizationCode): ShareableResourceDTO
    {
        // First check if it exists
        return new ShareableResourceDTO();
    }

    public function isResourceShareable(string $resourceId, string $organizationCode): bool
    {
        // Currently all topics can be shared
        return true;
    }

    public function hasSharePermission(string $resourceId, string $userId, string $organizationCode): bool
    {
        // Currently no share permission restrictions
        return true;
    }

    public function getResourceContent(string $resourceId, string $userId, string $organizationCode, int $page, int $pageSize): array
    {
        $result = $this->workspaceAppService->getMessagesByTopicId((int) $resourceId, $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    public function getResourceName(string $resourceId): string
    {
        return $this->workspaceAppService->getTopicDetail((int) $resourceId);
    }

    public function getResourceExtendList(array $list): array
    {
        // Extract resource_id, resource_id is the topic id
        $topicIds = array_column($list, 'resource_id');

        if (empty($topicIds)) {
            return $list;
        }

        // Query workspace name and workspace id by topic id collection
        try {
            $workspaceInfo = $this->workspaceAppService->getWorkspaceInfoByTopicIds($topicIds);
            // Assemble workspace name and workspace id through for loop
            foreach ($list as &$item) {
                $resourceId = $item['resource_id'];
                $item['extend'] = [];
                if (isset($workspaceInfo[$resourceId])) {
                    $item['extend'] = [
                        'workspace_id' => $workspaceInfo[$resourceId]['workspace_id'] ?? '',
                        'workspace_name' => $workspaceInfo[$resourceId]['workspace_name'] ?? '',
                    ];
                }
            }
            return $list;
        } catch (Exception $e) {
            // Log error but do not affect normal flow
            return $list;
        }
    }
}
