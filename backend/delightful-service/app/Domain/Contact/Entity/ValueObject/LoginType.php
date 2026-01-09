<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum LoginType: int
{
    // handmachinenumber + password
    case PhoneAndPassword = 1;

    // handmachinenumber + verifycode
    case PhoneAndCode = 2;
}
