<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject\Extra;

enum PermissionRange: int
{
    // alldepartment
    case ALL = 1;

    // fingerset
    case SELECT = 2;
}
