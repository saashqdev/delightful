<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentReleaseStatus: int
{
    case PERSONAL_USE = 0; // 个人使用
    case PUBLISHED_TO_ENTERPRISE = 1; // 发布到企业
    case PUBLISHED_TO_MARKET = 2; // 发布到市场
}
