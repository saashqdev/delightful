<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\SuperMagic\Domain\Agent\Event;

use Dtyq\SuperMagic\Domain\Agent\Entity\SuperMagicAgentEntity;

class SuperMagicAgentEnabledEvent
{
    public function __construct(public SuperMagicAgentEntity $superMagicAgentEntity)
    {
    }
}
