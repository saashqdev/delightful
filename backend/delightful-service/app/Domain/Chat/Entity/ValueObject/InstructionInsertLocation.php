<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionInsertLocation: int
{
    // messagecontentfront方
    case Before = 1;

    // messagecontentmiddle光标position
    case Cursor = 2;

    // messagecontentback方
    case After = 3;
}
