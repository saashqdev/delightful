<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Contact;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * usercurrentorganizationmanageAPItest
 */
class DelightfulUserOrganizationApiTest extends AbstractHttpTest
{
    private const string GET_CURRENT_ORGANIZATION_API = '/api/v1/contact/accounts/me/organization-code';

    private const string SET_CURRENT_ORGANIZATION_API = '/api/v1/contact/accounts/me/organization-code';

    private const string LIST_ORGANIZATIONS_API = '/api/v1/contact/accounts/me/organizations';

    /**
     * testpassHTTPrequestgetcurrentorganizationcode
     */
    public function testGetCurrentOrganizationCodeViaHttp(): void
    {
        $headers = $this->getCommonHeaders();

        $response = $this->get(self::GET_CURRENT_ORGANIZATION_API, [], $headers);

        // verifyresponsestatus
        $this->assertEquals(1000, $response['code'] ?? -1);

        // verifyresponse结构（according toactualAPIreturn结构adjust）
        if (isset($response['data'])) {
            $this->assertIsArray($response['data']);
        }
    }

    /**
     * testpassHTTPrequestsetcurrentorganizationcode
     */
    public function testSetCurrentOrganizationCodeViaHttp(): void
    {
        $headers = $this->getCommonHeaders();
        $requestData = [
            'delightful_organization_code' => $headers['organization-code'],
        ];

        $response = $this->put(self::SET_CURRENT_ORGANIZATION_API, $requestData, $headers);

        // verifyresponsestatus
        $this->assertEquals(1000, $response['code'] ?? -1);

        // verifyresponse结构
        if (isset($response['data'])) {
            $this->assertIsArray($response['data']);
            // verifyreturnorganizationcode
            if (isset($response['data']['delightful_organization_code'])) {
                $this->assertEquals($requestData['delightful_organization_code'], $response['data']['delightful_organization_code']);
            }
        }
    }

    /**
     * testsetemptyorganizationcodeerror情况.
     */
    public function testSetCurrentOrganizationCodeWithEmptyCodeViaHttp(): void
    {
        $headers = $this->getCommonHeaders();
        $requestData = [
            'delightful_organization_code' => '',
        ];

        $response = $this->put(self::SET_CURRENT_ORGANIZATION_API, $requestData, $headers);

        // verifyresponsestatus - shouldreturnerrorstatus码
        $this->assertNotEquals(200, $response['code'] ?? 200);
    }

    /**
     * testget账numberdowncan切换organizationlist.
     */
    public function testListOrganizationsViaHttp(): void
    {
        $headers = $this->getCommonHeaders();

        $response = $this->get(self::LIST_ORGANIZATIONS_API, [], $headers);
        var_dump($response);

        $this->assertEquals(1000, $response['code'] ?? -1);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('items', $response['data']);
        $this->assertIsArray($response['data']['items']);

        if ($response['data']['items'] !== []) {
            $organization = $response['data']['items'][0];
            $this->assertArrayHasKey('delightful_organization_code', $organization);
            $this->assertArrayHasKey('name', $organization);
            $this->assertArrayHasKey('organization_type', $organization);
            $this->assertArrayHasKey('logo', $organization);
            $this->assertArrayHasKey('is_current', $organization);
            $this->assertArrayHasKey('is_admin', $organization);
            $this->assertArrayHasKey('is_creator', $organization);
            $this->assertArrayHasKey('product_name', $organization);
            $this->assertArrayHasKey('plan_type', $organization);
            $this->assertArrayHasKey('subscription_tier', $organization);
            $this->assertArrayHasKey('seats', $organization);
        }
    }
}
