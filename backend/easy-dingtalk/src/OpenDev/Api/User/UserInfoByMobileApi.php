<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\User;

use BeDelightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get user information by mobile number.
 * @see https://oapi.dingtalk.com/topapi/v2/user/getbymobile
 */
class UserInfoByMobileApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/topapi/v2/user/getbymobile';
    }
}
