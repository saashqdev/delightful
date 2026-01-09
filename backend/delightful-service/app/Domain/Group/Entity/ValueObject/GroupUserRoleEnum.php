<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupUserRoleEnum: int
{
    // group owner
    case OWNER = 1;

    // administrator
    case ADMIN = 2;

    // normalmember
    case MEMBER = 3;
}
