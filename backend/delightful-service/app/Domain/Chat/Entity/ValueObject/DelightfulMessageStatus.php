<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;

enum DelightfulMessageStatus: int
{
    // 未读
    case Unread = 0;

    // 已读
    case Seen = 1;

    // 已查看（非纯文本的复杂typemessage，user点击了详情）
    case Read = 2;

    // 已withdraw
    case Revoked = 3;

    public function getStatusName(): string
    {
        return strtolower($this->name);
    }

    // according to控制messagetypegetmessagestatus
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
