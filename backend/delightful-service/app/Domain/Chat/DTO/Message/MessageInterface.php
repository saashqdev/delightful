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
 * 聊天message/控制message都needimplement的接口.
 *
 * @method mixed getContent() getmessagecontent
 * @method ?array getAttachments() getmessage附件
 * @method ?array getInstructs() getmessageinstruction
 */
interface MessageInterface
{
    public function toArray(bool $filterNull = false): array;

    public function getMessageTypeEnum(): ChatMessageType|ControlMessageType|IntermediateMessageType;
}
