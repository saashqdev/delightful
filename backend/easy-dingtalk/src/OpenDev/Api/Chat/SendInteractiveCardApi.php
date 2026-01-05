<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Chat;

use Delightful\EasyDingTalk\Kernel\Constants\Host;
use Delightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Delightful\SdkBase\Kernel\Constant\RequestMethod;

class SendInteractiveCardApi extends OpenDevApiAbstract
{
    public function getHost(): string
    {
        return Host::API_DING_TALK;
    }

    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/v1.0/im/interactiveCards/send';
    }
}
