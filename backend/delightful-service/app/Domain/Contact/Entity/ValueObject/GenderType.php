<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * gendertype.
 */
enum GenderType: int
{
    // unknown
    case Unknown = 0;

    // male
    case Male = 1;

    // female
    case Female = 2;
}
