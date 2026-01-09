<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentVersionStatus: int
{
    // approvalstreamstatus
    case APPROVAL_PENDING = 1; // 待approval
    case APPROVAL_IN_PROGRESS = 2; // approvalmiddle
    case APPROVAL_PASSED = 3; // approvalpass
    case APPROVAL_REJECTED = 4; // 已驳回

    // AI Agentpublish (企业)
    case ENTERPRISE_UNPUBLISHED = 5; // 未publish
    case ENTERPRISE_PUBLISHED = 6; // 已publish
    case ENTERPRISE_ENABLED = 7; // enable
    case ENTERPRISE_DISABLED = 8; // disable

    // AI Agentpublish (平台)
    case APP_MARKET_UNLISTED = 9; // 未up架
    case APP_MARKET_REVIEW = 10; // 审核middle
    case APP_MARKET_LISTED = 11; // 已up架
}
