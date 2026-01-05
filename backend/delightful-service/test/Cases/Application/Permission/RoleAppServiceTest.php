<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Permission;

use App\Application\Kernel\Enum\MagicOperationEnum;
use App\Application\Kernel\Enum\MagicResourceEnum;
use App\Application\Kernel\MagicPermission;
use App\Application\Permission\Service\RoleAppService;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\Context\ApplicationContext;
use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class RoleAppServiceTest extends HttpTestCase
{
    private RoleAppService $roleAppService;

    private PermissionDataIsolation $dataIsolation;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real DI container to fetch services
        $this->roleAppService = ApplicationContext::getContainer()->get(RoleAppService::class);
        $this->dataIsolation = PermissionDataIsolation::create('TEST_ORG', 'test_user_123');
    }

    public function testCreateAndQueryRole()
    {
        // Create a test role with timestamp to ensure uniqueness
        $uniqueName = 'Test Admin Role ' . time() . '_' . rand(1000, 9999);
        $roleEntity = new RoleEntity();
        $roleEntity->setName($uniqueName);
        $roleEntity->setOrganizationCode($this->dataIsolation->getCurrentOrganizationCode());
        $roleEntity->setStatus(1);

        $magicPermission = new MagicPermission();
        // Add test permission data
        $testPermissions = [
            $magicPermission->buildPermission(MagicResourceEnum::ADMIN_AI_MODEL->value, MagicOperationEnum::EDIT->value),
            $magicPermission->buildPermission(MagicResourceEnum::ADMIN_AI_IMAGE->value, MagicOperationEnum::QUERY->value),
        ];
        $roleEntity->setPermissions($testPermissions);

        // Add test user IDs
        $testUserIds = [
            'test_user_001',
            'test_user_002',
            'test_user_003',
        ];
        $roleEntity->setUserIds($testUserIds);

        // Save role
        $savedRole = $this->roleAppService->createRole($this->dataIsolation, $roleEntity);

        $this->assertNotNull($savedRole);
        $this->assertIsInt($savedRole->getId());
        $this->assertEquals($uniqueName, $savedRole->getName());

        // Verify permissions are saved
        $this->assertEquals($testPermissions, $savedRole->getPermissions());
        $this->assertCount(2, $savedRole->getPermissions());

        // Verify user IDs are saved
        $this->assertEquals($testUserIds, $savedRole->getUserIds());
        $this->assertCount(3, $savedRole->getUserIds());

        // Verify permission checks
        $this->assertTrue($savedRole->hasPermission($testPermissions[0]));
        $this->assertTrue($savedRole->hasPermission($testPermissions[1]));

        // Verify user checks
        $this->assertTrue($savedRole->hasUser('test_user_001'));
        $this->assertTrue($savedRole->hasUser('test_user_002'));
        $this->assertFalse($savedRole->hasUser('nonexistent_user'));

        // Fetch role by ID
        $foundRole = $this->roleAppService->show($this->dataIsolation, $savedRole->getId());
        $this->assertEquals($savedRole->getId(), $foundRole->getId());
        $this->assertEquals($savedRole->getName(), $foundRole->getName());

        // Verify fetched role has correct permissions and users
        $this->assertEquals($testPermissions, $foundRole->getPermissions());
        $this->assertEquals($testUserIds, $foundRole->getUserIds());

        // Clean up test data
        $this->roleAppService->destroy($this->dataIsolation, $savedRole->getId());

        return $savedRole;
    }

    public function testQueriesWithPagination()
    {
        // Create a few test roles
        $roles = [];
        for ($i = 1; $i <= 3; ++$i) {
            $roleEntity = new RoleEntity();
            $roleEntity->setName("Test Role {$i} " . uniqid());
            $roleEntity->setOrganizationCode($this->dataIsolation->getCurrentOrganizationCode());
            $roleEntity->setStatus(1);
            $roles[] = $this->roleAppService->createRole($this->dataIsolation, $roleEntity);
        }

        // Test paginated query
        $page = new Page(1, 2);
        $result = $this->roleAppService->queries($this->dataIsolation, $page);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(3, $result['total']);
        $this->assertLessThanOrEqual(2, count($result['list']));

        // Clean up test data
        foreach ($roles as $role) {
            $this->roleAppService->destroy($this->dataIsolation, $role->getId());
        }
    }

    public function testUpdateRole()
    {
        // Create role
        $roleEntity = new RoleEntity();
        $roleEntity->setName('Original Role ' . uniqid());
        $roleEntity->setOrganizationCode($this->dataIsolation->getCurrentOrganizationCode());
        $roleEntity->setStatus(1);

        $savedRole = $this->roleAppService->createRole($this->dataIsolation, $roleEntity);

        // Update role
        $updatedName = 'Updated Role ' . uniqid();
        $savedRole->setName($updatedName);

        $updatedRole = $this->roleAppService->updateRole($this->dataIsolation, $savedRole);

        $this->assertEquals($updatedName, $updatedRole->getName());

        // Verify database data updated
        $foundRole = $this->roleAppService->show($this->dataIsolation, $updatedRole->getId());
        $this->assertEquals($updatedName, $foundRole->getName());

        // Clean up test data
        $this->roleAppService->destroy($this->dataIsolation, $updatedRole->getId());
    }

    public function testGetPermissionTree()
    {
        $permissionTree = $this->roleAppService->getPermissionTree();

        $this->assertIsArray($permissionTree);
        $this->assertNotEmpty($permissionTree);

        // Validate tree structure
        foreach ($permissionTree as $platform) {
            $this->assertArrayHasKey('permission_key', $platform);
            $this->assertArrayHasKey('label', $platform);
            $this->assertArrayHasKey('children', $platform);
        }
    }

    public function testGetByNameReturnsNull()
    {
        $result = $this->roleAppService->getByName($this->dataIsolation, 'NonExistentRole');
        $this->assertNull($result);
    }
}
