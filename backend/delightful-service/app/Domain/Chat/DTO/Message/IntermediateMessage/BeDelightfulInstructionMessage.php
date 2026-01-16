<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\IntermediateMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\IntermediateMessageType;

class BeDelightfulInstructionMessage extends AbstractIntermediateMessageStruct
{
    protected function setMessageType(): void
    {
        $this->intermediateMessageType = IntermediateMessageType::BeDelightfulInstruction;
    }
}
