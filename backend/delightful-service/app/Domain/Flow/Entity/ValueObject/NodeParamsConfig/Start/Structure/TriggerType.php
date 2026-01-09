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
    // notouchhair
    case None = 0;

    // newmessageo clock
    case ChatMessage = 1;

    // openchatwindow
    case OpenChatWindow = 2;

    // schedule
    case Routine = 3;

    // parametercall
    case ParamCall = 4;

    // loopbodystartsectionpoint
    case LoopStart = 5;

    // etcpendingmessage
    case WaitMessage = 6;

    // addgoodfriendo clock
    case AddFriend = 7;

    public static function fromSeqType(ChatMessageType|ControlMessageType $seqType): TriggerType
    {
        $triggerType = TriggerType::None;
        if ($seqType instanceof ChatMessageType) {
            // chattouchhair
            $triggerType = TriggerType::ChatMessage;
        } elseif ($seqType === ControlMessageType::OpenConversation) {
            // openchatwindowtouchhair
            $triggerType = TriggerType::OpenChatWindow;
        } elseif ($seqType === ControlMessageType::AddFriendSuccess) {
            // addgoodfriend triggerhair
            $triggerType = TriggerType::AddFriend;
        }
        return $triggerType;
    }
}
