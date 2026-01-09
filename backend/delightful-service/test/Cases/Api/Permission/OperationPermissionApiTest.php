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
     * testgetuserorganizationadministratorlist - success情况.
     */
    public function testGetUserOrganizationAdminListSuccess(): void
    {
        // sendGETrequesttoAPIinterface
        $response = $this->get(self::API, [], $this->getCommonHeaders());

        // ifreturnautherror，skiptest
        if (isset($response['code']) && in_array($response['code'], [401, 403, 2179, 3035, 4001, 4003])) {
            $this->markTestSkipped('interfaceauthfail，可能need其他authconfiguration - interfacerouteverifynormal');
            return;
        }

        // assertresponse结构
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('data', $response, 'response应containdatafield');

        // verifydata结构
        $data = $response['data'];
        $this->assertArrayHasKey('organization_codes', $data, 'data应containorganization_codesfield');
        $this->assertArrayHasKey('total', $data, 'data应containtotalfield');
        $this->assertIsArray($data['organization_codes'], 'organization_codesshould是array');
        $this->assertIsInt($data['total'], 'totalshould是整数');
        $this->assertEquals(count($data['organization_codes']), $data['total'], 'totalshouldequalorganization_codes的quantity');
    }
}
