<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * temporarymessagecontent的type.
 */
enum IntermediateMessageType: string
{
    // 超levelMage的交互instruction
    case BeDelightfulInstruction = 'be_delightful_instruction';

    public function getName(): string
    {
        return $this->value;
    }
}
