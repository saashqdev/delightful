<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * user在organization中的status
 */
enum UserStatus: int
{
    // 0:冻结 (刚同过来没激活)
    case Frozen = 0;

    // 1:activated
    case Activated = 1;

    // 2:已离职
    case Resigned = 2;

    // 3:已退出
    case Exited = 3;
}
