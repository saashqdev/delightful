<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject\Item\Member;

enum MemberType: int
{
    case USER = 1;
    case DEPARTMENT = 2;
}
