<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionDisplayType: int
{
    // normalinstruction
    case Normal = 1;

    // systeminstruction
    case System = 2;
}
