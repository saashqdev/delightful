<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupStatusEnum: int
{
    // 正常
    case Normal = 1;

    // 解散
    case Disband = 2;
}
