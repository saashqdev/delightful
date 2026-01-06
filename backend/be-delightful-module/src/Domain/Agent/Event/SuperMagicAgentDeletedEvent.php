<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Domain\Agent\Event;

use Delightful\SuperDelightful\Domain\Agent\Entity\SuperDelightfulAgentEntity;

class SuperDelightfulAgentDeletedEvent
{
    public function __construct(public SuperDelightfulAgentEntity $superDelightfulAgentEntity)
    {
    }
}
