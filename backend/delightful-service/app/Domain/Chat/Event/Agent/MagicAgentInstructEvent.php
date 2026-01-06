<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event\Agent;

use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Infrastructure\Core\AbstractEvent;

class DelightfulAgentInstructEvent extends AbstractEvent
{
    public function __construct(
        public DelightfulAgentVersionEntity $magicBotVersionEntity,
    ) {
    }
}
