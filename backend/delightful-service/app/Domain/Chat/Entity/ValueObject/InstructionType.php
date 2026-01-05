<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionType: int
{
    // 流程指令
    case Flow = 1;

    // 对话指令
    case Conversation = 2;
}
