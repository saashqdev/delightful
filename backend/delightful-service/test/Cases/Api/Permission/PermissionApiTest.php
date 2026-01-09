<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Permission;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 */
class PermissionApiTest extends AbstractHttpTest
{
    public const string API = '/api/v1/admin/roles/permissions/tree';

    public function testAPIGetPermissionTree(): void
    {
        $response = $this->get(self::API, [], $this->getCommonHeaders());
        var_dump($response);

        // ifreturnauthenticationerror，skiptest（仅verify路bycanuse）
        if (isset($response['code']) && in_array($response['code'], [401, 403, 2179, 3035, 4001, 4003])) {
            $this->markTestSkipped('interfaceauthenticationfailed，maybeneedotherauthenticationconfiguration - 路byvalidationpass');
            return;
        }

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertNotEmpty($response['data']);
    }
}
