<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event\Agent;

interface AgentExecuteInterface
{
    public function agentExecEvent(UserCallAgentEvent $userCallAgentEvent);
}
