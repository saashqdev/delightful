<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionType: int
{
    // processinstruction
    case Flow = 1;

    // conversationinstruction
    case Conversation = 2;
}
