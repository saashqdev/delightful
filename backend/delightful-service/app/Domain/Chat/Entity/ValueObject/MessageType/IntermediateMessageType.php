<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * temporarymessagecontenttype.
 */
enum IntermediateMessageType: string
{
    // 超levelMageinteractioninstruction
    case BeDelightfulInstruction = 'be_delightful_instruction';

    public function getName(): string
    {
        return $this->value;
    }
}
