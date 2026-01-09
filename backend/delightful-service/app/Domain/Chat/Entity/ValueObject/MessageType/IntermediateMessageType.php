<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * temporarymessage内容的类型.
 */
enum IntermediateMessageType: string
{
    // 超级Mage的交互instruction
    case BeDelightfulInstruction = 'be_delightful_instruction';

    public function getName(): string
    {
        return $this->value;
    }
}
