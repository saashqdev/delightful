<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Flow\Event;

use App\Domain\Flow\Entity\MagicFlowToolSetEntity;

class MagicFLowToolSetSavedEvent
{
    public function __construct(public MagicFlowToolSetEntity $toolSetEntity, public bool $create)
    {
    }
}
