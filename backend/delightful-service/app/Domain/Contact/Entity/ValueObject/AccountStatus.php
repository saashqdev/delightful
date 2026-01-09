<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userstatus
 */
enum AccountStatus: int
{
    // disable
    case Disable = 0;

    // normal
    case Normal = 1;
}
