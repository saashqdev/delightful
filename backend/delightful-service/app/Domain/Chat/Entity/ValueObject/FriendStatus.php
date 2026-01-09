<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * good友status.
 */
enum FriendStatus: int
{
    // apply
    case Apply = 1;

    // agree
    case Agree = 2;

    // reject
    case Refuse = 3;

    // ignore
    case Ignore = 4;
}
