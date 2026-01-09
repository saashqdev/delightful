<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentVersionStatus: int
{
    // approval流status
    case APPROVAL_PENDING = 1; // 待approval
    case APPROVAL_IN_PROGRESS = 2; // approval中
    case APPROVAL_PASSED = 3; // approval通过
    case APPROVAL_REJECTED = 4; // 已驳回

    // AI Agentpublish (企业)
    case ENTERPRISE_UNPUBLISHED = 5; // 未publish
    case ENTERPRISE_PUBLISHED = 6; // 已publish
    case ENTERPRISE_ENABLED = 7; // 启用
    case ENTERPRISE_DISABLED = 8; // 禁用

    // AI Agentpublish (平台)
    case APP_MARKET_UNLISTED = 9; // 未上架
    case APP_MARKET_REVIEW = 10; // 审核中
    case APP_MARKET_LISTED = 11; // 已上架
}
