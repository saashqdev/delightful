<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum ContactIdMappingType: int
{
    // 组织编码
    case ORGANIZATION_CODE = 0;
}
