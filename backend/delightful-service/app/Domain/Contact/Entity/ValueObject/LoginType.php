<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum LoginType: int
{
    // hand机number + 密码
    case PhoneAndPassword = 1;

    // hand机number + verify码
    case PhoneAndCode = 2;
}
