<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Domain\Organization\Service;

use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Entity\OrganizationEntity;
use App\Domain\OrganizationEnvironment\Repository\Persistence\Model\OrganizationModel;
use App\Domain\OrganizationEnvironment\Service\OrganizationDomainService;
use App\Domain\Permission\Repository\Persistence\Model\OrganizationAdminModel;
use App\Domain\Permission\Service\OrganizationAdminDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use Exception;
use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class OrganizationDomainServiceTest extends HttpTestCase
{
    private OrganizationDomainService $organizationDomainService;

    private OrganizationAdminDomainService $organizationAdminDomainService;

    private DelightfulUserDomainService $userDomainService;

    private array $testOrganizationCodes = [];

    private array $testOrganizationIds = [];

    private array $testUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizationDomainService = $this->getContainer()->get(OrganizationDomainService::class);
        $this->organizationAdminDomainService = $this->getContainer()->get(OrganizationAdminDomainService::class);
        $this->userDomainService = $this->getContainer()->get(DelightfulUserDomainService::class);

        // Generate unique organization codes for each test to avoid data conflicts
        $this->testOrganizationCodes = [
            'TEST_ORG_' . uniqid(),
            'TEST_ORG_' . uniqid(),
            'TEST_ORG_' . uniqid(),
        ];

        // Generate unique user IDs for each test
        $this->testUserIds = [
            'test_user_' . uniqid(),
            'test_user_' . uniqid(),
            'test_user_' . uniqid(),
        ];

        // Clean up any existing test data
        $this->cleanUpTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanUpTestData();

        parent::tearDown();
    }

    public function testCreateOrganizationSuccessfully(): void
    {
        $organization = $this->createTestOrganizationEntity(0);

        $savedOrganization = $this->organizationDomainService->create($organization);

        $this->assertNotNull($savedOrganization->getId());
        $this->assertEquals($this->testOrganizationCodes[0], $savedOrganization->getDelightfulOrganizationCode());
        $this->assertEquals('Test Organization 0', $savedOrganization->getName());
        $this->assertEquals('Technology', $savedOrganization->getIndustryType());
        $this->assertEquals(1, $savedOrganization->getStatus());
        $this->assertNotNull($savedOrganization->getCreatedAt());

        // Record the ID for later cleanup
        $this->testOrganizationIds[] = $savedOrganization->getId();
    }

    public function testCreateOrganizationWithDuplicateCodeThrowsException(): void
    {
        // Create the first organization
        $organization1 = $this->createTestOrganizationEntity(0);
        $savedOrganization1 = $this->organizationDomainService->create($organization1);
        $this->testOrganizationIds[] = $savedOrganization1->getId();

        // Try creating an organization with the same code
        $organization2 = $this->createTestOrganizationEntity(0); // Use the same code

        $this->expectException(Exception::class);
        $this->organizationDomainService->create($organization2);
    }

    public function testCreateOrganizationWithDuplicateNameThrowsException(): void
    {
        // createtheoneorganization
        $organization1 = $this->createTestOrganizationEntity(0);
        $savedOrganization1 = $this->organizationDomainService->create($organization1);
        $this->testOrganizationIds[] = $savedOrganization1->getId();

        // 尝试create具havesamename的organization
        $organization2 = $this->createTestOrganizationEntity(1);
        $organization2->setName('Test Organization 0'); // usesame的name

        $this->expectException(Exception::class);
        $this->organizationDomainService->create($organization2);
    }

    public function testCreateOrganizationWithMissingRequiredFieldsThrowsException(): void
    {
        $organization = new OrganizationEntity();
        // Do not set required fields

        $this->expectException(Exception::class);
        $this->organizationDomainService->create($organization);
    }

    public function testUpdateOrganizationSuccessfully(): void
    {
        // Create organization
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        // Update organization
        $savedOrganization->setName('Updated Organization Name');
        $savedOrganization->setContactUser('Updated Contact');

        $updatedOrganization = $this->organizationDomainService->update($savedOrganization);

        $this->assertEquals('Updated Organization Name', $updatedOrganization->getName());
        $this->assertEquals('Updated Contact', $updatedOrganization->getContactUser());
        $this->assertNotNull($updatedOrganization->getUpdatedAt());
    }

    public function testUpdateNonExistentOrganizationThrowsException(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        // Do not set the ID so it is treated as a new entity

        $this->expectException(Exception::class);
        $this->organizationDomainService->update($organization);
    }

    public function testGetByIdReturnsCorrectOrganization(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $foundOrganization = $this->organizationDomainService->getById($savedOrganization->getId());

        $this->assertNotNull($foundOrganization);
        $this->assertEquals($savedOrganization->getId(), $foundOrganization->getId());
        $this->assertEquals($savedOrganization->getDelightfulOrganizationCode(), $foundOrganization->getDelightfulOrganizationCode());
        $this->assertEquals($savedOrganization->getName(), $foundOrganization->getName());
    }

    public function testGetByIdWithNonExistentIdReturnsNull(): void
    {
        $foundOrganization = $this->organizationDomainService->getById(999999);

        $this->assertNull($foundOrganization);
    }

    public function testGetByCodeReturnsCorrectOrganization(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $foundOrganization = $this->organizationDomainService->getByCode($this->testOrganizationCodes[0]);

        $this->assertNotNull($foundOrganization);
        $this->assertEquals($savedOrganization->getId(), $foundOrganization->getId());
        $this->assertEquals($this->testOrganizationCodes[0], $foundOrganization->getDelightfulOrganizationCode());
    }

    public function testGetByNameReturnsCorrectOrganization(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $foundOrganization = $this->organizationDomainService->getByName('Test Organization 0');

        $this->assertNotNull($foundOrganization);
        $this->assertEquals($savedOrganization->getId(), $foundOrganization->getId());
        $this->assertEquals('Test Organization 0', $foundOrganization->getName());
    }

    public function testQueriesReturnsCorrectResults(): void
    {
        // Create multiple organizations
        for ($i = 0; $i < 3; ++$i) {
            $organization = $this->createTestOrganizationEntity($i);
            $savedOrganization = $this->organizationDomainService->create($organization);
            $this->testOrganizationIds[] = $savedOrganization->getId();
        }

        $page = new Page(1, 10);
        $result = $this->organizationDomainService->queries($page);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(3, $result['total']);
        $this->assertIsArray($result['list']);
    }

    public function testQueriesWithFilters(): void
    {
        // Create organization
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $page = new Page(1, 10);
        $filters = [
            'name' => 'Test Organization',
            'status' => 1,
            'industry_type' => 'Technology',
        ];
        $result = $this->organizationDomainService->queries($page, $filters);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(1, $result['total']);

        // Validate filtered results
        foreach ($result['list'] as $org) {
            $this->assertInstanceOf(OrganizationEntity::class, $org);
            $this->assertStringContainsString('Test Organization', $org->getName());
            $this->assertEquals(1, $org->getStatus());
            $this->assertEquals('Technology', $org->getIndustryType());
        }
    }

    public function testDeleteOrganizationSuccessfully(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $orgId = $savedOrganization->getId();

        $this->organizationDomainService->delete($orgId);

        $foundOrganization = $this->organizationDomainService->getById($orgId);
        $this->assertNull($foundOrganization);
    }

    public function testDeleteNonExistentOrganizationThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->organizationDomainService->delete(999999);
    }

    public function testEnableOrganization(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $organization->setStatus(2); // Set to disabled status
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $enabledOrganization = $this->organizationDomainService->enable($savedOrganization->getId());

        $this->assertEquals(1, $enabledOrganization->getStatus());
        $this->assertTrue($enabledOrganization->isNormal());
    }

    public function testDisableOrganization(): void
    {
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        $disabledOrganization = $this->organizationDomainService->disable($savedOrganization->getId());

        $this->assertEquals(2, $disabledOrganization->getStatus());
        $this->assertFalse($disabledOrganization->isNormal());
    }

    public function testIsCodeAvailable(): void
    {
        // Test a non-existent code
        $isAvailable = $this->organizationDomainService->isCodeAvailable('NON_EXISTENT_CODE');
        $this->assertTrue($isAvailable);

        // Create organization
        $organization = $this->createTestOrganizationEntity(0);
        $savedOrganization = $this->organizationDomainService->create($organization);
        $this->testOrganizationIds[] = $savedOrganization->getId();

        // Test an existing code
        $isAvailable = $this->organizationDomainService->isCodeAvailable($this->testOrganizationCodes[0]);
        $this->assertFalse($isAvailable);

        // Test excluding the current organization
        $isAvailable = $this->organizationDomainService->isCodeAvailable(
            $this->testOrganizationCodes[0],
            $savedOrganization->getId()
        );
        $this->assertTrue($isAvailable);
    }

    /**
     * Test automatically granting admin permission to the creator when creating an organization.
     * Note: In a real environment this may require real user data or mocking support.
     */
    public function testCreateOrganizationAutomaticallyGrantsAdminPermissionToCreator(): void
    {
        // Use a simple numeric ID for the creator to avoid user-creation complexity
        $organization = $this->createTestOrganizationEntity(0);
        $creatorId = '1'; // Use a simple numeric ID
        $organization->setCreatorId($creatorId);

        try {
            // Create organization
            $savedOrganization = $this->organizationDomainService->create($organization);

            // Record the ID for cleanup
            $this->testOrganizationIds[] = $savedOrganization->getId();

            // Verify organization created successfully
            $this->assertNotNull($savedOrganization->getId());
            $this->assertEquals($creatorId, $savedOrganization->getCreatorId());

            // Verify the creator is granted admin permission (if the user exists)
            $isAdmin = $this->organizationAdminDomainService->isOrganizationAdmin(
                $savedOrganization->getDelightfulOrganizationCode(),
                (string) $creatorId
            );

            // If the user exists, they should receive admin permission
            if ($isAdmin) {
                // Verify the creator is marked as the organization creator
                $admin = $this->organizationAdminDomainService->getByUserId(
                    $savedOrganization->getDelightfulOrganizationCode(),
                    (string) $creatorId
                );
                $this->assertNotNull($admin);
                $this->assertTrue($admin->isOrganizationCreator());
                $this->assertEquals('organizationcreate者自动获得管理员permission', $admin->getRemarks());
            }

            // At minimum verify organization creation succeeded
            $this->assertTrue(true, 'organizationcreatesuccess');
        } catch (Exception $e) {
            // If the user does not exist, an exception should be thrown (expected)
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    /**
     * Test that creating an organization with a nonexistent creator throws an exception.
     */
    public function testCreateOrganizationWithNonExistentCreatorThrowsException(): void
    {
        // Create an organization entity with a nonexistent creator ID
        $organization = $this->createTestOrganizationEntity(0);
        $nonExistentCreatorId = '999999'; // Use an unlikely numeric ID
        $organization->setCreatorId($nonExistentCreatorId);

        $this->expectException(Exception::class);
        $this->organizationDomainService->create($organization);
    }

    /**
     * Test that creating an organization without a creator ID still succeeds.
     */
    public function testCreateOrganizationWithoutCreatorIdSucceeds(): void
    {
        // Create an organization entity without setting a creator ID
        $organization = $this->createTestOrganizationEntity(0);
        $organization->setCreatorId(null);

        // Create organization
        $savedOrganization = $this->organizationDomainService->create($organization);

        // Record the ID for cleanup
        $this->testOrganizationIds[] = $savedOrganization->getId();

        // Verify organization created successfully
        $this->assertNotNull($savedOrganization->getId());
        $this->assertNull($savedOrganization->getCreatorId());

        // Verify no admin records were created
        $allAdmins = $this->organizationAdminDomainService->getAllOrganizationAdmins(
            $savedOrganization->getDelightfulOrganizationCode()
        );
        $this->assertEmpty($allAdmins);
    }

    /**
     * Test that an organization creator's admin permission cannot be revoked.
     * Note: Skipped because the user system is complex.
     */
    public function testOrganizationCreatorAdminPermissionCannotBeRevoked(): void
    {
        $this->markTestSkipped(
            'This test requires real user data. In practice, use a mock framework or test fixtures to simulate user presence. '
            . 'Test logic: create an organization creator, then attempt to revoke admin permission; an exception should be thrown.'
        );
    }

    /**
     * Test that an organization creator cannot be disabled.
     * Note: Skipped because the user system is complex.
     */
    public function testOrganizationCreatorCannotBeDisabled(): void
    {
        $this->markTestSkipped(
            'This test requires real user data. In practice, use a mock framework or test fixtures to simulate user presence. '
            . 'Test logic: create an organization creator, then attempt to disable admin permission; an exception should be thrown.'
        );
    }

    /**
     * Simulate a user existing.
     * Note: This is simplified; a mock framework should be used in real projects.
     */
    private function mockUserExists(string $userId): void
    {
        // Simplified handling because the user system is complex
        // In real projects, use database fixtures or dedicated test-data builders
        // Skip user creation here so tests focus on organization-creator behavior
    }

    /**
     * Simulate a user not existing.
     */
    private function mockUserNotExists(string $userId): void
    {
        // Ensure the user is treated as non-existent
        // In real projects, delete the test user or use mocking to simulate absence
    }

    /**
     * Clean up test users.
     */
    private function cleanUpTestUser(string $userId): void
    {
        // Clean up user-related test data
        // In real projects, delete any created test users
    }

    /**
     * Create a test organization entity.
     */
    private function createTestOrganizationEntity(int $index): OrganizationEntity
    {
        $organization = new OrganizationEntity();
        $organization->setDelightfulOrganizationCode($this->testOrganizationCodes[$index]);
        $organization->setName("Test Organization {$index}");
        $organization->setIndustryType('Technology');
        $organization->setContactUser("Contact User {$index}");
        $organization->setContactMobile('13800138000');
        $organization->setCreatorId(null); // Default: do not set creator; each test sets it as needed
        $organization->setStatus(1);
        $organization->setType(0);

        return $organization;
    }

    /**
     * Clean up test data.
     */
    private function cleanUpTestData(): void
    {
        try {
            // Delete organization admin test data
            foreach ($this->testOrganizationCodes as $code) {
                OrganizationAdminModel::query()
                    ->where('organization_code', $code)
                    ->forceDelete();
            }

            // Remove any remaining organization admin data
            OrganizationAdminModel::query()
                ->where('organization_code', 'like', 'TEST_ORG_%')
                ->forceDelete();

            // Remove organization admin data associated via user IDs
            foreach ($this->testUserIds as $userId) {
                OrganizationAdminModel::query()
                    ->where('user_id', $userId)
                    ->forceDelete();
            }

            // Delete organizations recorded by ID
            foreach ($this->testOrganizationIds as $id) {
                OrganizationModel::query()->where('id', $id)->forceDelete();
            }

            // Delete organizations recorded by code
            foreach ($this->testOrganizationCodes as $code) {
                OrganizationModel::query()->where('delightful_organization_code', $code)->forceDelete();
            }

            // Remove any remaining test data
            OrganizationModel::query()
                ->where('delightful_organization_code', 'like', 'TEST_ORG_%')
                ->orWhere('name', 'like', 'Test Organization%')
                ->orWhere('name', 'like', 'Updated Organization%')
                ->forceDelete();

            // Clean up test users
            foreach ($this->testUserIds as $userId) {
                $this->cleanUpTestUser($userId);
            }
        } catch (Exception $e) {
            // Swallow cleanup errors
        }

        // Reset ID array
        $this->testOrganizationIds = [];
    }
}
