<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

enum LoginType: int
{
    // 手机号 + 密码
    case PhoneAndPassword = 1;

    // 手机号 + verify码
    case PhoneAndCode = 2;
}
