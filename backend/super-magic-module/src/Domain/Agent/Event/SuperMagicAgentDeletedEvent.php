<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Domain\Agent\Event;

use Dtyq\SuperMagic\Domain\Agent\Entity\SuperMagicAgentEntity;

class SuperMagicAgentDeletedEvent
{
    public function __construct(public SuperMagicAgentEntity $superMagicAgentEntity)
    {
    }
}
