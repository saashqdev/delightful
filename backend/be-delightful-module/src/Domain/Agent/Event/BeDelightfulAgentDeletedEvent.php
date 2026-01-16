<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\Agent\Event;

use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;

class BeDelightfulAgentDeletedEvent
{
    public function __construct(public BeDelightfulAgentEntity $superMagicAgentEntity)
    {
    }
}
