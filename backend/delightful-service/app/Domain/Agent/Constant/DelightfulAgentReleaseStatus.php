<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentReleaseStatus: int
{
    case PERSONAL_USE = 0; // 个人use
    case PUBLISHED_TO_ENTERPRISE = 1; // publishto企业
    case PUBLISHED_TO_MARKET = 2; // publishto市场
}
