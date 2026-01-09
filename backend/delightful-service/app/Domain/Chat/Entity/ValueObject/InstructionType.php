<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionType: int
{
    // 流程instruction
    case Flow = 1;

    // 对话instruction
    case Conversation = 2;
}
