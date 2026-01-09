<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

/**
 * textstructureformmessage,存infrom动download url thenagaintimeuploadbusinessscenario,如approvalform.
 */
class TextFormMessage extends TextMessage
{
    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::TextForm;
    }
}
