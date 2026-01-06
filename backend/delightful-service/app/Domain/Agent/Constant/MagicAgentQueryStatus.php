<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentQueryStatus: int
{
    case UNPUBLISHED = 1; // 未发布
    case PUBLISHED = 2; // 已发布
    case ALL = 3; // 所有
}
