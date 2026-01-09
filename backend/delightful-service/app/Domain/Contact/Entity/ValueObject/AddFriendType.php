<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum AddFriendType: int
{
    // addgood友apply
    case APPLY = 1;

    // addgood友pass
    case PASS = 2;

    // addgood友reject
    case REFUSE = 3;
}
