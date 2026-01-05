<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace HyperfTest\Cases\Permission;

use App\Application\Permission\Service\OrganizationAdminAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Permission\Entity\OrganizationAdminEntity;
use App\Infrastructure\Core\ValueObject\Page;
use Exception;
use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class OrganizationAdminAppServiceTest extends HttpTestCase
{
    private OrganizationAdminAppService $organizationAdminAppService;

    private string $testOrganizationCode = 'test_org_code';

    private string $testUserId;

    private string $testGrantorUserId = 'test_grantor_user_id';

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizationAdminAppService = $this->getContainer()->get(OrganizationAdminAppService::class);

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

    public function testGrantOrganizationAdminPermission(): void
    {
        // Grant organization admin permission
        $organizationAdmin = $this->organizationAdminAppService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId,
            $this->testGrantorUserId,
            'Test grant'
        );

        $this->assertInstanceOf(OrganizationAdminEntity::class, $organizationAdmin);
        $this->assertEquals($this->testUserId, $organizationAdmin->getUserId());
        $this->assertEquals($this->testGrantorUserId, $organizationAdmin->getGrantorUserId());
        $this->assertEquals('Test grant', $organizationAdmin->getRemarks());
        $this->assertTrue($organizationAdmin->isEnabled());
    }

    public function testGetOrganizationAdminByUserId(): void
    {
        // Grant permission first
        $this->organizationAdminAppService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId,
            $this->testGrantorUserId
        );

        // Get organization admin by user ID
        $organizationAdmin = $this->organizationAdminAppService->getByUserId(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId
        );

        $this->assertNotNull($organizationAdmin);
        $this->assertEquals($this->testUserId, $organizationAdmin->getUserId());
    }

    public function testQueriesOrganizationAdminList(): void
    {
        // Create a few organization admins first
        $testUserIds = [];
        for ($i = 1; $i <= 3; ++$i) {
            $uniqueUserId = 'test_user_' . uniqid() . "_{$i}";
            $testUserIds[] = $uniqueUserId;
            $this->organizationAdminAppService->grant(
                $this->createDataIsolation($this->testOrganizationCode),
                $uniqueUserId,
                $this->testGrantorUserId
            );
        }

        // Query organization admin list
        $page = new Page(1, 10);
        $result = $this->organizationAdminAppService->queries($this->createDataIsolation($this->testOrganizationCode), $page);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(3, $result['total']);
        $this->assertIsArray($result['list']);
    }

    public function testShowOrganizationAdminDetails(): void
    {
        // Grant permission first
        $organizationAdmin = $this->organizationAdminAppService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId,
            $this->testGrantorUserId
        );

        // Get details
        $details = $this->organizationAdminAppService->show($this->createDataIsolation($this->testOrganizationCode), $organizationAdmin->getId());

        $this->assertIsArray($details);
        $this->assertArrayHasKey('organization_admin', $details);
        $this->assertArrayHasKey('user_info', $details);
        $this->assertArrayHasKey('grantor_info', $details);
        $this->assertArrayHasKey('department_info', $details);

        $organizationAdminData = $details['organization_admin'];
        $this->assertInstanceOf(OrganizationAdminEntity::class, $organizationAdminData);
        $this->assertEquals($this->testUserId, $organizationAdminData->getUserId());
    }

    public function testDestroyOrganizationAdmin(): void
    {
        // Grant permission first
        $organizationAdmin = $this->organizationAdminAppService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId,
            $this->testGrantorUserId
        );

        $organizationAdminId = $organizationAdmin->getId();

        // Delete organization admin
        $this->organizationAdminAppService->destroy($this->createDataIsolation($this->testOrganizationCode), $organizationAdminId);

        // Verify deletion
        $deletedOrganizationAdmin = $this->organizationAdminAppService->getByUserId(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserId
        );
        $this->assertNull($deletedOrganizationAdmin);
    }

    private function createDataIsolation(string $organizationCode): DataIsolation
    {
        return DataIsolation::simpleMake($organizationCode);
    }

    private function cleanUpTestData(): void
    {
        try {
            // Clean up primary test user
            if (isset($this->testUserId)) {
                $organizationAdmin = $this->organizationAdminAppService->getByUserId(
                    $this->createDataIsolation($this->testOrganizationCode),
                    $this->testUserId
                );
                if ($organizationAdmin) {
                    $this->organizationAdminAppService->destroy($this->createDataIsolation($this->testOrganizationCode), $organizationAdmin->getId());
                }
            }

            // Clean up other test users (pattern-based)
            for ($i = 1; $i <= 5; ++$i) {
                $testUserId = "test_user_{$i}";
                $organizationAdmin = $this->organizationAdminAppService->getByUserId(
                    $this->createDataIsolation($this->testOrganizationCode),
                    $testUserId
                );
                if ($organizationAdmin) {
                    $this->organizationAdminAppService->destroy($this->createDataIsolation($this->testOrganizationCode), $organizationAdmin->getId());
                }
            }
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
}
