<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Kernel;

use App\Application\Kernel\Contract\DelightfulPermissionInterface;
use App\Application\Kernel\DelightfulPermission;
use HyperfTest\HttpTestCase;
use InvalidArgumentException;

/**
 * @internal
 */
class DelightfulPermissionEnumTest extends HttpTestCase
{
    private DelightfulPermissionInterface $permissionEnum;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionEnum = di(DelightfulPermissionInterface::class);
    }

    public function testGetOperations()
    {
        $operations = $this->permissionEnum->getOperations();

        $this->assertIsArray($operations);
        $this->assertCount(2, $operations);
        $this->assertContains('query', $operations);
        $this->assertContains('manage', $operations);
        $this->assertNotContains('export', $operations); // export is not in operations list
    }

    public function testParsePermissionWithValidKey()
    {
        $permissionKey = 'admin.ai.model_management.query';

        $parsed = $this->permissionEnum->parsePermission($permissionKey);

        $this->assertIsArray($parsed);
        $this->assertEquals('Admin.ai.model_management', $parsed['resource']);
        $this->assertEquals('query', $parsed['operation']);
    }

    public function testParsePermissionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid permission key format');

        $this->permissionEnum->parsePermission('invalid.key');
    }

    public function testGetResourceLabelWithInvalidResource()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a resource type: invalid_resource');

        $this->permissionEnum->getResourceLabel('invalid_resource');
    }

    public function testGetOperationLabelWithInvalidOperation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not an operation type: invalid_operation');

        $this->permissionEnum->getOperationLabel('invalid_operation');
    }

    public function testGenerateAllPermissions()
    {
        $permissions = $this->permissionEnum->generateAllPermissions();

        $this->assertIsArray($permissions);
        // shouldhave 2 resource × 2 operationas = 4 permission(rowexceptexportoperationas)
        $this->assertCount(4, $permissions);

        // checkpermissionstructure
        foreach ($permissions as $permission) {
            $this->assertArrayHasKey('permission_key', $permission);
            $this->assertArrayHasKey('resource', $permission);
            $this->assertArrayHasKey('operation', $permission);
            $this->assertArrayHasKey('resource_label', $permission);
            $this->assertArrayHasKey('operation_label', $permission);

            // checkspecificvalue
            $this->assertContains($permission['resource'], $this->permissionEnum->getResources());
            $this->assertContains($permission['operation'], $this->permissionEnum->getOperations());
        }

        // checkspecificpermissionwhetherexistsin
        $permissionKeys = array_column($permissions, 'permission_key');
        $this->assertContains('admin.ai.model_management.query', $permissionKeys);
        $this->assertContains('admin.ai.model_management.manage', $permissionKeys);
        $this->assertContains('admin.ai.image_generation.query', $permissionKeys);
        $this->assertContains('admin.ai.image_generation.manage', $permissionKeys);
    }

    public function testIsValidPermissionWithValidKeys()
    {
        // testalllocalpermission
        $this->assertTrue($this->permissionEnum->isValidPermission(DelightfulPermission::ALL_PERMISSIONS));

        // testvalidpermissiongroup合
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.model_management.query'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.model_management.manage'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.image_generation.query'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.image_generation.manage'));
    }

    public function testIsValidPermissionWithInvalidKeys()
    {
        // testinvalidpermissionkey
        $this->assertFalse($this->permissionEnum->isValidPermission('invalid_permission'));
        $this->assertFalse($this->permissionEnum->isValidPermission('Admin.ai.invalid_resource.query'));
        $this->assertFalse($this->permissionEnum->isValidPermission('Admin.ai.model_management.invalid_operation'));
        $this->assertFalse($this->permissionEnum->isValidPermission('short.key'));
    }

    public function testGetPermissionTree()
    {
        $tree = $this->permissionEnum->getPermissionTree();

        // defaultsituationdown(nonplatformorganization)notcontain platform platformsectionpoint
        $this->assertIsArray($tree);
        $this->assertGreaterThanOrEqual(1, count($tree));

        // findto Admin platformsectionpointconductenterone步validation
        $platformsByKey = [];
        foreach ($tree as $node) {
            $platformsByKey[$node['permission_key']] = $node;
        }
        $this->assertArrayHasKey('admin', $platformsByKey);
        $this->assertArrayNotHasKey('platform', $platformsByKey);
        $platform = $platformsByKey['admin'];

        $this->assertEquals('managebackplatform', $platform['label']);
        $this->assertArrayHasKey('children', $platform);
        $this->assertNotEmpty($platform['children']);

        foreach ($platform['children'] as $module) {
            $this->assertArrayHasKey('label', $module);
            $this->assertArrayHasKey('children', $module);
            foreach ($module['children'] as $resource) {
                $this->assertArrayHasKey('children', $resource);
                foreach ($resource['children'] as $operation) {
                    $this->assertTrue($operation['is_leaf']);
                }
            }
        }
    }

    /**
     * test私havemethodisValidCombinationlinefor
     * passgenerateAllPermissionsbetween接test.
     */
    public function testIsValidCombinationThroughGenerateAllPermissions()
    {
        $permissions = $this->permissionEnum->generateAllPermissions();

        // ensurenothaveexportoperationaspermission
        foreach ($permissions as $permission) {
            $this->assertNotEquals('export', $permission['operation']);
        }
    }

    /**
     * testside界situation.
     */
    public function testEdgeCases()
    {
        // testemptystring
        $this->assertFalse($this->permissionEnum->isResource(''));
        $this->assertFalse($this->permissionEnum->isOperation(''));
        $this->assertFalse($this->permissionEnum->isValidPermission(''));

        // testnullvalueprocess(PHPwillconvertforstring)
        $this->assertFalse($this->permissionEnum->isValidPermission('null'));
    }

    /**
     * testcategoryimplementcorrectinterface.
     */
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(
            DelightfulPermissionInterface::class,
            $this->permissionEnum
        );
    }
}
