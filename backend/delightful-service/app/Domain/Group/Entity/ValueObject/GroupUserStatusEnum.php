<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupUserStatusEnum: int
{
    // normal
    case Normal = 1;

    // be禁言
    case Mute = 2;
}
