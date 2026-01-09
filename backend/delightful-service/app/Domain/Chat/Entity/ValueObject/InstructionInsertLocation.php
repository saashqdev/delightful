<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionInsertLocation: int
{
    // messagecontent前方
    case Before = 1;

    // messagecontent中光标position
    case Cursor = 2;

    // messagecontent后方
    case After = 3;
}
