<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

/**
 * 文本cardmessage.
 */
class TextCardMessage extends AbstractChatMessageStruct
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $url = null;

    protected ?string $btnTxt = null;

    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::TextCard;
    }
}
