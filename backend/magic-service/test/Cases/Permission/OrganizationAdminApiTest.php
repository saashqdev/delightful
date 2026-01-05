<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace HyperfTest\Cases\Permission;

use App\Application\Permission\Service\OrganizationAdminAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class OrganizationAdminApiTest extends HttpTestCase
{
    private OrganizationAdminAppService $superAdminAppService;

    private string $testOrganizationCode = 'test001';

    private string $testUserId;

    /**
     * Store the token after login.
     */
    private static string $accessToken = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdminAppService = $this->getContainer()->get(OrganizationAdminAppService::class);

        // Generate a unique user ID per test to avoid data conflicts
        $this->testUserId = 'test_user_' . uniqid();

        // Clean up any existing test data
        $this->cleanUpTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanUpTestData();

        parent::tearDown();
    }

    public function testGetSuperAdminList(): void
    {
        // Simulate HTTP request to get the list
        $response = $this->get('/api/v1/admin/organization-admin/list?page=1&page_size=10', [], $this->getTestHeaders());

        // Validate response format and status
        $this->assertIsArray($response, 'Response should be an array');

        $this->assertEquals(1000, $response['code'] ?? 0, 'Response code should be 1000');
        $this->assertArrayHasKey('data', $response, 'Response should contain data field');

        $data = $response['data'];
        $this->assertIsArray($data);
        $this->assertArrayHasKey('list', $data);
        $this->assertIsArray($data['list']);
    }

    public function testGrantSuperAdminPermission(): void
    {
        $userId = 'usi_71f7b56bec00b0cd9f9daba18caa7a4c';
        $response = $this->post('/api/v1/admin/organization-admin/grant', [
            'user_id' => $userId,
            'remarks' => 'Test grant via API',
        ], $this->getTestHeaders());

        $this->assertEquals(1000, $response['code'] ?? 0, 'Response code should be 1000');
    }

    private function createDataIsolation(string $organizationCode): DataIsolation
    {
        return DataIsolation::simpleMake($organizationCode);
    }

    private function cleanUpTestData(): void
    {
    }

    /**
     * Get headers for test requests.
     */
    private function getTestHeaders(): array
    {
        return [
            'Authorization' => env('TEST_TOKEN'),
            'organization-code' => env('TEST_ORGANIZATION_CODE'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
