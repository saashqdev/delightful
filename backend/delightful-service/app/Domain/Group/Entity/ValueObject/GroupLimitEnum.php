<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupLimitEnum: int
{
    // 普通group chatmostbigperson数limit
    case NormalGroup = 1000;
}
