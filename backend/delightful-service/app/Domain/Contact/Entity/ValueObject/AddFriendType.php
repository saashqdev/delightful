<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum AddFriendType: int
{
    // addgoodfriendapply
    case APPLY = 1;

    // addgoodfriendpass
    case PASS = 2;

    // addgoodfriendreject
    case REFUSE = 3;
}
