<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupLimitEnum: int
{
    // 普通group chatmost大person数限制
    case NormalGroup = 1000;
}
