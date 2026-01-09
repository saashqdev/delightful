<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;

enum DelightfulMessageStatus: int
{
    // not读
    case Unread = 0;

    // already读
    case Seen = 1;

    // alreadyview（non纯textcomplextypemessage，userpoint击detail）
    case Read = 2;

    // alreadywithdraw
    case Revoked = 3;

    public function getStatusName(): string
    {
        return strtolower($this->name);
    }

    // according tocontrolmessagetypegetmessagestatus
    public static function getMessageStatusByControlMessageType(ControlMessageType $controlMessageType): DelightfulMessageStatus
    {
        return match ($controlMessageType) {
            ControlMessageType::SeenMessages => DelightfulMessageStatus::Seen,
            ControlMessageType::ReadMessage => DelightfulMessageStatus::Read,
            ControlMessageType::RevokeMessage => DelightfulMessageStatus::Revoked,
            default => DelightfulMessageStatus::Seen
        };
    }
}
