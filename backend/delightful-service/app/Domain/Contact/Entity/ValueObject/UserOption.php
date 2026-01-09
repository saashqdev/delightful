<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * user的一些optional操作.
 */
enum UserOption: int
{
    // hidden
    case Hidden = 1;
}
