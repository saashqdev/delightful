<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentQueryStatus: int
{
    case UNPUBLISHED = 1; // notpublish
    case PUBLISHED = 2; // alreadypublish
    case ALL = 3; // 所have
}
