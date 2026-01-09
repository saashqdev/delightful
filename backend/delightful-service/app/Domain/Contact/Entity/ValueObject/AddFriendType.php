<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum AddFriendType: int
{
    // 添加好友申请
    case APPLY = 1;

    // 添加好友pass
    case PASS = 2;

    // 添加好友拒绝
    case REFUSE = 3;
}
