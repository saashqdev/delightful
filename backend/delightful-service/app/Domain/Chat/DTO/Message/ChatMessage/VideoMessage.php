<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

class VideoMessage extends FileMessage
{
    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::Video;
    }
}
