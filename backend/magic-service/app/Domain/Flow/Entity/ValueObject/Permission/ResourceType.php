<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Flow\Entity\ValueObject\Permission;

enum ResourceType: int
{
    case FlowCode = 1;
    case UserId = 2;
}
