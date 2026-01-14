<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Department;

use BeDelightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get department details.
 * @see https://open.dingtalk.com/document/isvapp/query-department-details0-v2
 */
class GetDeptByIdApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/topapi/v2/department/get';
    }
}
