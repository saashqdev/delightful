<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Oauth2;

use Delightful\EasyDingTalk\Kernel\Constants\Host;
use Delightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Delightful\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get access token for enterprise internal application.
 * @see https://open.dingtalk.com/document/orgapp/obtain-the-access_token-of-an-internal-app
 */
class AccessTokenApi extends OpenDevApiAbstract
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
        return '/v1.0/oauth2/accessToken';
    }
}
