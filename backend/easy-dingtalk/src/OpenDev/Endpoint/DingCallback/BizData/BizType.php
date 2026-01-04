<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Endpoint\DingCallback\BizData;

/**
 * https://open.dingtalk.com/document/isvapp/authorization-event-1.
 */
class BizType
{
    /**
     * Data is the latest status of the third-party enterprise application ticket suiteTicket
     */
    public const SuiteTicket = 2;

    /**
     * Data is enterprise authorization.
     */
    public const CorpAuthorization = 4;
}
