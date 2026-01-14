<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Test\OpenDev\Provider\User;

use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetListByDeptIdParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetUserInfoByUserIdParameter;
use BeDelightful\EasyDingTalk\Test\OpenDev\OpenDevEndpointBaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class UserEndpointTestEndpoint extends OpenDevEndpointBaseTestCase
{
    public function testGetListByDeptId()
    {
        $openDev = $this->createOpenDevFactory('first');
        $param = new GetListByDeptIdParameter($openDev->accessTokenEndpoint->get());
        $param->setDeptId(837865406);
        $list = $openDev->userEndpoint->getListByDeptId($param);
        $this->assertIsArray($list->getUserList());
    }
}
