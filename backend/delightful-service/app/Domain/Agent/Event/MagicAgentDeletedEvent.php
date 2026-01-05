<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Event;

use App\Domain\Agent\Entity\MagicAgentEntity;

class MagicAgentDeletedEvent
{
    public function __construct(public MagicAgentEntity $agentEntity)
    {
    }
}
