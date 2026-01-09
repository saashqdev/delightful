<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionInsertLocation: int
{
    // message内容前方
    case Before = 1;

    // message内容中光标位置
    case Cursor = 2;

    // message内容后方
    case After = 3;
}
