<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\IntermediateMessageType;

/**
 * chatmessage/控制message都needimplement的interface.
 *
 * @method mixed getContent() getmessagecontent
 * @method ?array getAttachments() getmessageattachment
 * @method ?array getInstructs() getmessageinstruction
 */
interface MessageInterface
{
    public function toArray(bool $filterNull = false): array;

    public function getMessageTypeEnum(): ChatMessageType|ControlMessageType|IntermediateMessageType;
}
