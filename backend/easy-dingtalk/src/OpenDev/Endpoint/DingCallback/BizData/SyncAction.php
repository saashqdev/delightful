<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\DingCallback\BizData;

class SyncAction
{
    /**
     * Refresh ticket.
     */
    public const SuiteTicket = 'suite_ticket';

    /**
     * Authorization.
     */
    public const OrgSuiteAuth = 'org_suite_auth';

    /**
     * Revoke authorization.
     */
    public const OrgSuiteRelieve = 'org_suite_relieve';
}
