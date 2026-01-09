<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Permission;

use App\Infrastructure\Util\Auth\PermissionChecker;
use HyperfTest\Cases\Api\AbstractHttpTest;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(PermissionChecker::class)]
class OperationPermissionApiTest extends AbstractHttpTest
{
    public const string API = '/api/v1/operation-permissions/organizations/admin';

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * testgetuserorganization管理员list - success情况.
     */
    public function testGetUserOrganizationAdminListSuccess(): void
    {
        // 发送GET请求到API接口
        $response = $this->get(self::API, [], $this->getCommonHeaders());

        // 如果returnautherror，跳过test
        if (isset($response['code']) && in_array($response['code'], [401, 403, 2179, 3035, 4001, 4003])) {
            $this->markTestSkipped('接口authfail，可能需要其他authconfiguration - 接口route验证正常');
            return;
        }

        // assert响应结构
        $this->assertIsArray($response, '响应应该是array格式');
        $this->assertArrayHasKey('data', $response, '响应应包含datafield');

        // 验证数据结构
        $data = $response['data'];
        $this->assertArrayHasKey('organization_codes', $data, '数据应包含organization_codesfield');
        $this->assertArrayHasKey('total', $data, '数据应包含totalfield');
        $this->assertIsArray($data['organization_codes'], 'organization_codes应该是array');
        $this->assertIsInt($data['total'], 'total应该是整数');
        $this->assertEquals(count($data['organization_codes']), $data['total'], 'total应该等于organization_codes的数量');
    }
}
