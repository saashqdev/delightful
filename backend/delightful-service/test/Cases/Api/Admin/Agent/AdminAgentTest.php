<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Cases\Api\Admin\Agent;

use App\Application\Admin\Agent\Service\AdminAgentAppService;
use App\Interfaces\Admin\DTO\Request\QueryPageAgentDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use HyperfTest\Cases\BaseTest;

/**
 * @internal
 */
class AdminAgentTest extends BaseTest
{
    public function testQueryAgents()
    {
        $userId = '2';
        $organizationCode = 'DT001';
        $delightfulUserAuthorization = new DelightfulUserAuthorization();
        $delightfulUserAuthorization->setId($userId);
        $delightfulUserAuthorization->setOrganizationCode($organizationCode);
        $service = di(AdminAgentAppService::class);
        $queriesAgents = $service->queriesAgents($delightfulUserAuthorization, new QueryPageAgentDTO());
    }
}
