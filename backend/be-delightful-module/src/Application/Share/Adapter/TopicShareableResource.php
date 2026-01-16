<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Share\Adapter;

use Delightful\BeDelightful\Application\Share\DTO\ShareableResourceDTO;
use Delightful\BeDelightful\Application\Share\Factory\Facade\ResourceFactoryInterface;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Exception;

class TopicShareableResource implements ResourceFactoryInterface 
{
 
    public function __construct(
    private readonly WorkspaceAppService $workspaceAppService) 
{
 
}
 
    public function createResource(string $resourceId, string $userId, string $organizationCode): ShareableResourceDTO 
{
 // query whether Exist return new ShareableResourceDTO(); 
}
 
    public function isResourceShareable(string $resourceId, string $organizationCode): bool 
{
 // Alltopic Share return true; 
}
 
    public function hasSharepermission (string $resourceId, string $userId, string $organizationCode): bool 
{
 // Don't haveSharepermission return true; 
}
 
    public function getResourceContent(string $resourceId, string $userId, string $organizationCode, int $page, int $pageSize): array 
{
 $result = $this->workspaceAppService->getMessagesByTopicId((int) $resourceId, $page, $pageSize); if (empty($result)) 
{
 return []; 
}
 return $result; 
}
 
    public function getResourceName(string $resourceId): string 
{
 return $this->workspaceAppService->getTopicDetail((int) $resourceId); 
}
 
    public function getResourceExtendlist (array $list): array 
{
 // resource_id, resource_id yes topic id $topicIds = array_column($list, 'resource_id'); if (empty($topicIds)) 
{
 return $list; 
}
 // Through topic id Collectionquery workspace Nameworkspace id try 
{
 $workspaceinfo = $this->workspaceAppService->getWorkspaceinfo ByTopicIds($topicIds); // Through for loop workspace Nameworkspace idGroup foreach ($list as &$item) 
{
 $resourceId = $item['resource_id']; $item['extend'] = []; if (isset($workspaceinfo [$resourceId])) 
{
 $item['extend'] = [ 'workspace_id' => $workspaceinfo [$resourceId]['workspace_id'] ?? '', 'workspace_name' => $workspaceinfo [$resourceId]['workspace_name'] ?? '', ]; 
}
 
}
 return $list; 
}
 catch (Exception $e) 
{
 // record ErrorLogNormal return $list; 
}
 
}
 
}
 
