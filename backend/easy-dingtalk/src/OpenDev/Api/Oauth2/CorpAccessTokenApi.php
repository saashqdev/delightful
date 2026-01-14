<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Oauth2;

use BeDelightful\EasyDingTalk\Kernel\Constants\Host;
use BeDelightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get access token of third-party application authorized enterprise.
 * @see https://open.dingtalk.com/document/isvapp/obtain-the-access_token-of-the-authorized-enterprise
 */
class CorpAccessTokenApi extends OpenDevApiAbstract
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
        return '/v1.0/oauth2/corpAccessToken';
    }
}
