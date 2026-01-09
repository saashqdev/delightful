<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionInsertLocation: int
{
    // messagecontentfrontside
    case Before = 1;

    // messagecontentmiddlecursorposition
    case Cursor = 2;

    // messagecontentbackside
    case After = 3;
}
