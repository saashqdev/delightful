<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\User;

use Delightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Delightful\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get department user details.
 * @see https://open.dingtalk.com/document/orgapp/queries-the-complete-information-of-a-department-user
 */
class UserListApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/topapi/v2/user/list';
    }
}
