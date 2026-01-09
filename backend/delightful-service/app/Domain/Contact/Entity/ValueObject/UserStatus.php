<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userinorganizationmiddlestatus
 */
enum UserStatus: int
{
    // 0:freeze (just同passcomenotactivate)
    case Frozen = 0;

    // 1:activated
    case Activated = 1;

    // 2:already离职
    case Resigned = 2;

    // 3:alreadyexit
    case Exited = 3;
}
