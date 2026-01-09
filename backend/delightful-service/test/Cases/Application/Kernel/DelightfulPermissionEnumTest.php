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
        // should有 2 个资源 × 2 个操作 = 4 个permission（排除export操作）
        $this->assertCount(4, $permissions);

        // checkpermission结构
        foreach ($permissions as $permission) {
            $this->assertArrayHasKey('permission_key', $permission);
            $this->assertArrayHasKey('resource', $permission);
            $this->assertArrayHasKey('operation', $permission);
            $this->assertArrayHasKey('resource_label', $permission);
            $this->assertArrayHasKey('operation_label', $permission);

            // check具体value
            $this->assertContains($permission['resource'], $this->permissionEnum->getResources());
            $this->assertContains($permission['operation'], $this->permissionEnum->getOperations());
        }

        // check特定permission是否存在
        $permissionKeys = array_column($permissions, 'permission_key');
        $this->assertContains('admin.ai.model_management.query', $permissionKeys);
        $this->assertContains('admin.ai.model_management.manage', $permissionKeys);
        $this->assertContains('admin.ai.image_generation.query', $permissionKeys);
        $this->assertContains('admin.ai.image_generation.manage', $permissionKeys);
    }

    public function testIsValidPermissionWithValidKeys()
    {
        // test全局permission
        $this->assertTrue($this->permissionEnum->isValidPermission(DelightfulPermission::ALL_PERMISSIONS));

        // testvalid的permissiongroup合
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.model_management.query'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.model_management.manage'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.image_generation.query'));
        $this->assertTrue($this->permissionEnum->isValidPermission('admin.ai.image_generation.manage'));
    }

    public function testIsValidPermissionWithInvalidKeys()
    {
        // testinvalid的permission键
        $this->assertFalse($this->permissionEnum->isValidPermission('invalid_permission'));
        $this->assertFalse($this->permissionEnum->isValidPermission('Admin.ai.invalid_resource.query'));
        $this->assertFalse($this->permissionEnum->isValidPermission('Admin.ai.model_management.invalid_operation'));
        $this->assertFalse($this->permissionEnum->isValidPermission('short.key'));
    }

    public function testGetPermissionTree()
    {
        $tree = $this->permissionEnum->getPermissionTree();

        // default情况下（非平台organization）不contain platform 平台节点
        $this->assertIsArray($tree);
        $this->assertGreaterThanOrEqual(1, count($tree));

        // 找到 Admin 平台节点进行进一步校验
        $platformsByKey = [];
        foreach ($tree as $node) {
            $platformsByKey[$node['permission_key']] = $node;
        }
        $this->assertArrayHasKey('admin', $platformsByKey);
        $this->assertArrayNotHasKey('platform', $platformsByKey);
        $platform = $platformsByKey['admin'];

        $this->assertEquals('管理后台', $platform['label']);
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
     * test私有methodisValidCombination的行为
     * passgenerateAllPermissions间接test.
     */
    public function testIsValidCombinationThroughGenerateAllPermissions()
    {
        $permissions = $this->permissionEnum->generateAllPermissions();

        // ensure没有export操作的permission
        foreach ($permissions as $permission) {
            $this->assertNotEquals('export', $permission['operation']);
        }
    }

    /**
     * test边界情况.
     */
    public function testEdgeCases()
    {
        // test空string
        $this->assertFalse($this->permissionEnum->isResource(''));
        $this->assertFalse($this->permissionEnum->isOperation(''));
        $this->assertFalse($this->permissionEnum->isValidPermission(''));

        // testnullvalueprocess（PHPwillconvert为string）
        $this->assertFalse($this->permissionEnum->isValidPermission('null'));
    }

    /**
     * test类implement了correct的interface.
     */
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(
            DelightfulPermissionInterface::class,
            $this->permissionEnum
        );
    }
}
