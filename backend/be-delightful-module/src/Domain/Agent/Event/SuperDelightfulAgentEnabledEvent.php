<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Event;

use BeDelightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;

class BeDelightfulAgentEnabledEvent
{
    public function __construct(public BeDelightfulAgentEntity $beDelightfulAgentEntity)
    {
    }
}
