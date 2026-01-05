<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Tiptap\CustomExtension\ValueObject;

enum InstructionContentType: string
{
    case TEXT = 'text';
    case QUICK_INSTRUCTION = 'quick-instruction';
}
