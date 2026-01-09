<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;

enum TriggerType: int
{
    // 无触hair
    case None = 0;

    // 新messageo clock
    case ChatMessage = 1;

    // openchat窗口
    case OpenChatWindow = 2;

    // schedule
    case Routine = 3;

    // parametercall
    case ParamCall = 4;

    // 循环bodystartsectionpoint
    case LoopStart = 5;

    // etc待message
    case WaitMessage = 6;

    // add好友o clock
    case AddFriend = 7;

    public static function fromSeqType(ChatMessageType|ControlMessageType $seqType): TriggerType
    {
        $triggerType = TriggerType::None;
        if ($seqType instanceof ChatMessageType) {
            // chat触hair
            $triggerType = TriggerType::ChatMessage;
        } elseif ($seqType === ControlMessageType::OpenConversation) {
            // openchat窗口触hair
            $triggerType = TriggerType::OpenChatWindow;
        } elseif ($seqType === ControlMessageType::AddFriendSuccess) {
            // add好友触hair
            $triggerType = TriggerType::AddFriend;
        }
        return $triggerType;
    }
}
