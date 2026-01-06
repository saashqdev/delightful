<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\MCP\Facade\Admin;

use App\Application\Contact\Service\DelightfulUserSettingAppService;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class MCPBeDelightfulProjectSettingAdminApi extends AbstractMCPAdminApi
{
    #[Inject]
    protected DelightfulUserSettingAppService $magicUserSettingAppService;

    public function save(string $projectId)
    {
        $authorization = $this->getAuthorization();
        $userSetting = $this->magicUserSettingAppService->saveProjectMcpServerConfig($authorization, $projectId, $this->request->input('servers', []));
        return $userSetting->getValue();
    }

    public function get(string $projectId)
    {
        $authorization = $this->getAuthorization();
        $userSetting = $this->magicUserSettingAppService->getProjectMcpServerConfig($authorization, $projectId);
        if ($userSetting) {
            return $userSetting->getValue();
        }
        return [];
    }
}
