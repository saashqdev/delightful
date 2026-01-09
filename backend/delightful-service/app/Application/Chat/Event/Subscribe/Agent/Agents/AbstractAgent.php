<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\Agent\Agents;

use App\Domain\Chat\Event\Agent\UserCallAgentEvent;
use RuntimeException;

class AbstractAgent implements AgentInterface
{
    public function execute(UserCallAgentEvent $event)
    {
        throw new RuntimeException('executemethod未implement');
    }
}
