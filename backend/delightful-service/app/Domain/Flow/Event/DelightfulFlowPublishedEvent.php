<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Event;

use App\Domain\Flow\Entity\DelightfulFlowEntity;

class DelightfulFlowPublishedEvent
{
    public function __construct(
        public DelightfulFlowEntity $delightfulFlowEntity,
    ) {
    }
}
