<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api;

use Delightful\EasyDingTalk\Kernel\Constants\Host;
use Delightful\EasyDingTalk\Kernel\Contracts\ApiManager\ApiAbstract;

abstract class OpenDevApiAbstract extends ApiAbstract
{
    public function getHost(): string
    {
        return Host::OPEN_DING_TALK;
    }
}
