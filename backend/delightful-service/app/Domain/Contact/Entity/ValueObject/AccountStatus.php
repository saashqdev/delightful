<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * 用户状态
 */
enum AccountStatus: int
{
    // 禁用
    case Disable = 0;

    // 正常
    case Normal = 1;
}
