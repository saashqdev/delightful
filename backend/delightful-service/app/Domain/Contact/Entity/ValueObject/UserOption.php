<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * user一些optional操as.
 */
enum UserOption: int
{
    // hidden
    case Hidden = 1;
}
