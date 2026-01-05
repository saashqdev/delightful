<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\Department;

use Delightful\EasyDingTalk\OpenDev\Result\Department\AllParentDepartmentResult;
use Delightful\EasyDingTalk\OpenDev\Result\Department\DeptResult;

class DepartmentFactory
{
    public static function createDeptResultByResult(array $rawData): DeptResult
    {
        return new DeptResult($rawData);
    }

    public static function createAllParentDepartmentResult(array $rawData): AllParentDepartmentResult
    {
        return new AllParentDepartmentResult($rawData);
    }
}
