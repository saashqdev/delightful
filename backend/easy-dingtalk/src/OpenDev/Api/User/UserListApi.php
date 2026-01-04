<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\User;

use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

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
