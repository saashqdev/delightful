<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum AddFriendType: int
{
    // add好友apply
    case APPLY = 1;

    // add好友pass
    case PASS = 2;

    // add好友reject
    case REFUSE = 3;
}
