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
    // 无触发
    case None = 0;

    // 新message时
    case ChatMessage = 1;

    // openchat窗口
    case OpenChatWindow = 2;

    // schedule
    case Routine = 3;

    // parametercall
    case ParamCall = 4;

    // 循环体start节点
    case LoopStart = 5;

    // etc待message
    case WaitMessage = 6;

    // 添加好友时
    case AddFriend = 7;

    public static function fromSeqType(ChatMessageType|ControlMessageType $seqType): TriggerType
    {
        $triggerType = TriggerType::None;
        if ($seqType instanceof ChatMessageType) {
            // chat触发
            $triggerType = TriggerType::ChatMessage;
        } elseif ($seqType === ControlMessageType::OpenConversation) {
            // openchat窗口触发
            $triggerType = TriggerType::OpenChatWindow;
        } elseif ($seqType === ControlMessageType::AddFriendSuccess) {
            // 添加好友触发
            $triggerType = TriggerType::AddFriend;
        }
        return $triggerType;
    }
}
