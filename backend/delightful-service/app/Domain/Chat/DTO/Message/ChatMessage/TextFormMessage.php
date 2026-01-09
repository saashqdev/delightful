<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

/**
 * text结构的formmessage，存in自动download url thenagaintimeupload的业务场景，如approvalform。
 */
class TextFormMessage extends TextMessage
{
    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::TextForm;
    }
}
