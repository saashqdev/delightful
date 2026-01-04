<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

class UnknowChatMessage extends AbstractChatMessageStruct
{
    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::Unknown;
    }
}
