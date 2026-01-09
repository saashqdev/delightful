<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * 好友status.
 */
enum FriendStatus: int
{
    // 申请
    case Apply = 1;

    // 同意
    case Agree = 2;

    // 拒绝
    case Refuse = 3;

    // 忽略
    case Ignore = 4;
}
