<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Permission;

use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\Application\Kernel\DelightfulPermission;
use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 */
class RoleApiTest extends AbstractHttpTest
{
    public const string CREATE_SUB_ADMIN_API = '/api/v1/admin/roles/sub-admins';

    public const string SUB_ADMIN_API = '/api/v1/admin/roles/sub-admins/';

    /**
     * test子管理员listquery.
     */
    public function testGetSubAdminListAndById(): void
    {
        // === test getSubAdminList ===
        $listResp = $this->get(self::CREATE_SUB_ADMIN_API, [], $this->getCommonHeaders());

        $this->assertIsArray($listResp);
        $this->assertEquals(1000, $listResp['code'] ?? null);
    }

    public function testCreateSubAdminSuccess(): void
    {
        // === testcreate子管理员 ===
        $delightfulPermission = new DelightfulPermission();
        $testPermissions = [
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_MODEL->value, DelightfulOperationEnum::EDIT->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_IMAGE->value, DelightfulOperationEnum::QUERY->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN->value, DelightfulOperationEnum::EDIT->value),
        ];
        $requestData = [
            'name' => 'test子管理员角色',
            'status' => 1,
            'permissions' => $testPermissions,
            'user_ids' => ['usi_343adbdbe8a026226311c67bdea152ea', 'usi_71f7b56bec00b0cd9f9daba18caa7a4c'],
        ];

        $response = $this->post(
            self::CREATE_SUB_ADMIN_API,
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertIsArray($response);

        // checksuccess响应结构
        if (isset($response['code']) && $response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response);
            $this->assertIsArray($response['data']);
            $this->assertArrayHasKey('id', $response['data']);
            $this->assertArrayHasKey('name', $response['data']);
            $this->assertEquals($requestData['name'], $response['data']['name']);
            $this->assertEquals($requestData['status'], $response['data']['status']);
        }
        // === testcreate子管理员END ===

        // === testupdate子管理员 ===
        $id = $response['data']['id'];

        $testPermissions = [
            $delightfulPermission->buildPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN->value, DelightfulOperationEnum::EDIT->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_MODEL->value, DelightfulOperationEnum::QUERY->value),
        ];

        $requestData = [
            'name' => 'update的子管理员角色' . rand(100, 999),
            'status' => 0,
            'permissions' => $testPermissions,
            'user_ids' => ['usi_343adbdbe8a026226311c67bdea152ea'],
        ];

        $response = $this->put(
            self::SUB_ADMIN_API . $id,
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertIsArray($response);
        $this->assertEquals(1000, $response['code']);

        // checksuccess响应结构
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('name', $response['data']);
        $this->assertEquals($requestData['name'], $response['data']['name']);
        $this->assertEquals($requestData['status'], $response['data']['status']);
        // === testupdate子管理员END ===

        // === testquery子管理员 ===
        $detailResp = $this->get(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        // assert详情接口响应结构与数据
        $this->assertIsArray($detailResp);
        $this->assertEquals(1000, $detailResp['code'] ?? null);

        $expectedDetailStructure = [
            'id' => '',
            'name' => '',
            'status' => 0,
            'permissions' => [],
            'user_ids' => [],
            'created_at' => null,
            'updated_at' => null,
        ];

        $this->assertArrayValueTypesEquals(
            $expectedDetailStructure,
            $detailResp['data'] ?? [],
            '子管理员详情接口响应结构不符合预期',
            false,
            false
        );

        // 核对数据content
        $this->assertEquals($id, $detailResp['data']['id'] ?? null);
        $this->assertEquals($requestData['name'], $detailResp['data']['name'] ?? null);
        $this->assertEquals($requestData['status'], $detailResp['data']['status'] ?? null);

        // === testquery子管理员END ===

        // === testdelete子管理员 ===
        // calldelete接口
        $deleteResp = $this->delete(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        $this->assertIsArray($deleteResp);
        $this->assertEquals(1000, $deleteResp['code']);

        // 再次query应当return角色不存在或空
        $detailResp = $this->get(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        // 预期这里会returnerror码，具体according to业务而定，只要非1000即可
        $this->assertNotEquals(1000, $detailResp['code'] ?? null);
        // === testdelete子管理员END ===
    }

    /**
     * testgetuserpermission树接口.
     */
    public function testGetUserPermissionTree(): void
    {
        // call接口
        $response = $this->get(
            '/api/v1/permissions/me',
            [],
            $this->getCommonHeaders()
        );

        // assert基础响应结构
        $this->assertIsArray($response);
        $this->assertEquals(1000, $response['code'] ?? null);

        // assert data field存在且为array
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);

        // 如果 data 非空，简单校验节点结构
        if (! empty($response['data'])) {
            $this->assertArrayHasKey('permission_key', $response['data']);
        }
    }
}
