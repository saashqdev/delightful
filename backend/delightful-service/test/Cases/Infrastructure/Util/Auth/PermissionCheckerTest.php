<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Infrastructure\Util\Auth;

use App\Application\Kernel\SuperPermissionEnum;
use App\Infrastructure\Util\Auth\PermissionChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PermissionChecker::class)]
class PermissionCheckerTest extends TestCase
{
    /**
     * test全局管理员permissioncheck.
     */
    public function testGlobalAdminHasPermission(): void
    {
        // 模拟configuration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001', '13800000002'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
        ];

        // 全局管理员should有所有permission
        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000001',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000002',
            SuperPermissionEnum::MODEL_CONFIG_ADMIN,
            $permissions
        ));
    }

    /**
     * test特定permissioncheck.
     */
    public function testSpecificPermission(): void
    {
        // 模拟configuration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
            SuperPermissionEnum::MODEL_CONFIG_ADMIN->value => ['13800000004', '13800000007'],
        ];

        // 有特定permission的user
        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        // 一个usercan有多个permission
        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000004',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000004',
            SuperPermissionEnum::MODEL_CONFIG_ADMIN,
            $permissions
        ));

        // 没有此permission的user
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::MODEL_CONFIG_ADMIN,
            $permissions
        ));
    }

    /**
     * test无permission的情况.
     */
    public function testNoPermission(): void
    {
        // 模拟configuration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
        ];

        // 不在permissionlist中的user
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000099',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        // permission不存在的情况
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::HIDE_USER_OR_DEPT,
            $permissions
        ));
    }

    /**
     * usedata提供者testpermissioncheck.
     */
    #[DataProvider('permissionCheckDataProvider')]
    public function testPermissionCheckWithDataProvider(
        string $mobile,
        SuperPermissionEnum $permission,
        array $permissions,
        bool $expected
    ): void {
        $this->assertEquals(
            $expected,
            PermissionChecker::checkPermission($mobile, $permission, $permissions)
        );
    }

    /**
     * testdata提供者method.
     */
    public static function permissionCheckDataProvider(): array
    {
        return [
            '全局管理员' => ['13800000001', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'], SuperPermissionEnum::FLOW_ADMIN->value => []], true],
            '特定permissionuser' => ['13800000003', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], true],
            '无permissionuser' => ['13800000099', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
            'permission不存在' => ['13800000003', SuperPermissionEnum::HIDE_USER_OR_DEPT, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
            '空手机号' => ['', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'], SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
        ];
    }
}
