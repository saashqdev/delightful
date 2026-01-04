<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Flow\Event;

use App\Domain\Flow\Entity\MagicFlowEntity;

class MagicFlowPublishedEvent
{
    public function __construct(
        public MagicFlowEntity $magicFlowEntity,
    ) {
    }
}
