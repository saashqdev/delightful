<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\Department;

use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Get all parent department list of a specified user.
 * @see https://open.dingtalk.com/document/orgapp/queries-the-list-of-all-parent-departments-of-a-user
 */
class GetAllParentDepartmentByUserApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/topapi/v2/department/listparentbyuser';
    }
}
