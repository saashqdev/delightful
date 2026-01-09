<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\DTO\Message\DelightfulMessageStruct;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageTrait;
use App\Domain\Chat\DTO\Message\StreamMessageInterface;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

/**
 * originalmessage，canfastspeeduseatonetheseneedtemporaryforwarddataformat，原样output.
 */
class RawMessage extends DelightfulMessageStruct implements StreamMessageInterface
{
    use StreamMessageTrait;

    protected array $rawData = [];

    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::Raw;
    }
}
