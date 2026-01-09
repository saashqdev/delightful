<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

enum DelightfulAgentVersionStatus: int
{
    // approvalstreamstatus
    case APPROVAL_PENDING = 1; // pendingapproval
    case APPROVAL_IN_PROGRESS = 2; // approvalmiddle
    case APPROVAL_PASSED = 3; // approvalpass
    case APPROVAL_REJECTED = 4; // alreadyrefutereturn

    // AI Agentpublish (enterprise)
    case ENTERPRISE_UNPUBLISHED = 5; // notpublish
    case ENTERPRISE_PUBLISHED = 6; // alreadypublish
    case ENTERPRISE_ENABLED = 7; // enable
    case ENTERPRISE_DISABLED = 8; // disable

    // AI Agentpublish (platform)
    case APP_MARKET_UNLISTED = 9; // notupframework
    case APP_MARKET_REVIEW = 10; // reviewmiddle
    case APP_MARKET_LISTED = 11; // alreadyupframework
}
