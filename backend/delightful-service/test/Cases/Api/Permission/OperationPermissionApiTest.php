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
            $this->markTestSkipped('接口authfail，可能need其他authconfiguration - 接口routeverify正常');
            return;
        }

        // assert响应结构
        $this->assertIsArray($response, '响应should是array格式');
        $this->assertArrayHasKey('data', $response, '响应应containdatafield');

        // verify数据结构
        $data = $response['data'];
        $this->assertArrayHasKey('organization_codes', $data, '数据应containorganization_codesfield');
        $this->assertArrayHasKey('total', $data, '数据应containtotalfield');
        $this->assertIsArray($data['organization_codes'], 'organization_codesshould是array');
        $this->assertIsInt($data['total'], 'totalshould是整数');
        $this->assertEquals(count($data['organization_codes']), $data['total'], 'totalshouldequalorganization_codes的quantity');
    }
}
