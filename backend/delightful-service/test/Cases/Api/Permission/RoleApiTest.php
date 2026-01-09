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
     * testchildadministratorlistquery.
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
        // === testcreatechildadministrator ===
        $delightfulPermission = new DelightfulPermission();
        $testPermissions = [
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_MODEL->value, DelightfulOperationEnum::EDIT->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_IMAGE->value, DelightfulOperationEnum::QUERY->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN->value, DelightfulOperationEnum::EDIT->value),
        ];
        $requestData = [
            'name' => 'testchildadministratorrole',
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

        // checksuccessresponsestructure
        if (isset($response['code']) && $response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response);
            $this->assertIsArray($response['data']);
            $this->assertArrayHasKey('id', $response['data']);
            $this->assertArrayHasKey('name', $response['data']);
            $this->assertEquals($requestData['name'], $response['data']['name']);
            $this->assertEquals($requestData['status'], $response['data']['status']);
        }
        // === testcreatechildadministratorEND ===

        // === testupdatechildadministrator ===
        $id = $response['data']['id'];

        $testPermissions = [
            $delightfulPermission->buildPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN->value, DelightfulOperationEnum::EDIT->value),
            $delightfulPermission->buildPermission(DelightfulResourceEnum::ADMIN_AI_MODEL->value, DelightfulOperationEnum::QUERY->value),
        ];

        $requestData = [
            'name' => 'updatechildadministratorrole' . rand(100, 999),
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

        // checksuccessresponsestructure
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('name', $response['data']);
        $this->assertEquals($requestData['name'], $response['data']['name']);
        $this->assertEquals($requestData['status'], $response['data']['status']);
        // === testupdatechildadministratorEND ===

        // === testquerychildadministrator ===
        $detailResp = $this->get(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        // assertdetailinterfaceresponsestructureanddata
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
            'childadministratordetailinterfaceresponsestructurenotconformexpected',
            false,
            false
        );

        // 核todatacontent
        $this->assertEquals($id, $detailResp['data']['id'] ?? null);
        $this->assertEquals($requestData['name'], $detailResp['data']['name'] ?? null);
        $this->assertEquals($requestData['status'], $detailResp['data']['status'] ?? null);

        // === testquerychildadministratorEND ===

        // === testdeletechildadministrator ===
        // calldeleteinterface
        $deleteResp = $this->delete(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        $this->assertIsArray($deleteResp);
        $this->assertEquals(1000, $deleteResp['code']);

        // againtimequeryshouldwhenreturnrolenotexistsinorempty
        $detailResp = $this->get(self::SUB_ADMIN_API . $id, [], $this->getCommonHeaders());
        // expectedthiswithinwillreturnerrorcode,specificaccording tobusinesswhileset,as long asnon1000immediatelycan
        $this->assertNotEquals(1000, $detailResp['code'] ?? null);
        // === testdeletechildadministratorEND ===
    }

    /**
     * testgetuserpermissiontreeinterface.
     */
    public function testGetUserPermissionTree(): void
    {
        // callinterface
        $response = $this->get(
            '/api/v1/permissions/me',
            [],
            $this->getCommonHeaders()
        );

        // assertfoundationresponsestructure
        $this->assertIsArray($response);
        $this->assertEquals(1000, $response['code'] ?? null);

        // assert data fieldexistsinandforarray
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);

        // if data nonempty,simplesinglevalidationsectionpointstructure
        if (! empty($response['data'])) {
            $this->assertArrayHasKey('permission_key', $response['data']);
        }
    }
}
