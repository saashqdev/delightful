<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event\Agent;

use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Infrastructure\Core\AbstractEvent;

/**
 * agentthrow了exception.
 */
class UserCallAgentFailEvent extends AbstractEvent
{
    public function __construct(
        public DelightfulSeqEntity $seqEntity,
    ) {
    }
}
