<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Entity\ValueObject\Visibility;

enum VisibilityType: int
{
    case All = 1;
    case SPECIFIC = 2;
}
