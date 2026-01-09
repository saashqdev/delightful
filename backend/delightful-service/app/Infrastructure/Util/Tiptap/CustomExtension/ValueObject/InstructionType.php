<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Tiptap\CustomExtension\ValueObject;

enum InstructionType: int
{
    // single-select
    case SINGLE_CHOICE = 1;

    // switch
    case SWITCH = 2;

    // text
    case TEXT = 3;
}
