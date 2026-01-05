<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Test\OpenDev\Provider\Department;

use Delightful\EasyDingTalk\OpenDev\Parameter\Department\GetAllParentDepartmentByUserParameter;
use Delightful\EasyDingTalk\OpenDev\Parameter\Department\GetDeptByIdParameter;
use Delightful\EasyDingTalk\OpenDev\Parameter\Department\GetSubParameter;
use Delightful\EasyDingTalk\Test\OpenDev\OpenDevEndpointBaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class DepartmentEndpointTestEndpoint extends OpenDevEndpointBaseTestCase
{
    public function testGetSUb()
    {
        $openDev = $this->createOpenDevFactory('first');
        $param = new GetSubParameter($openDev->accessTokenEndpoint->get());
        $param->setDeptId(837530544);
        $list = $openDev->departmentEndpoint->getSub($param);
        $this->assertIsArray($list);
    }
}
