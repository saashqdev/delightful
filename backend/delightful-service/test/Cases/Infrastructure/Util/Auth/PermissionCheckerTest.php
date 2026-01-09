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
     * testalllocaladministratorpermissioncheck.
     */
    public function testGlobalAdminHasPermission(): void
    {
        // mockconfiguration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001', '13800000002'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
        ];

        // alllocaladministratorshouldhave havepermission
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
     * testspecificpermissioncheck.
     */
    public function testSpecificPermission(): void
    {
        // mockconfiguration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
            SuperPermissionEnum::MODEL_CONFIG_ADMIN->value => ['13800000004', '13800000007'],
        ];

        // havespecificpermissionuser
        $this->assertTrue(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        // oneusercanhavemultiplepermission
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

        // nothavethispermissionuser
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::MODEL_CONFIG_ADMIN,
            $permissions
        ));
    }

    /**
     * testnopermissionsituation.
     */
    public function testNoPermission(): void
    {
        // mockconfiguration
        $permissions = [
            SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'],
            SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003', '13800000004'],
        ];

        // notinpermissionlistmiddleuser
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000099',
            SuperPermissionEnum::FLOW_ADMIN,
            $permissions
        ));

        // permissionnotexistsinsituation
        $this->assertFalse(PermissionChecker::checkPermission(
            '13800000003',
            SuperPermissionEnum::HIDE_USER_OR_DEPT,
            $permissions
        ));
    }

    /**
     * usedataprovidepersontestpermissioncheck.
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
     * testdataprovidepersonmethod.
     */
    public static function permissionCheckDataProvider(): array
    {
        return [
            'alllocaladministrator' => ['13800000001', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'], SuperPermissionEnum::FLOW_ADMIN->value => []], true],
            'specificpermissionuser' => ['13800000003', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], true],
            'nopermissionuser' => ['13800000099', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
            'permissionnotexistsin' => ['13800000003', SuperPermissionEnum::HIDE_USER_OR_DEPT, [SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
            'emptyhandmachinenumber' => ['', SuperPermissionEnum::FLOW_ADMIN, [SuperPermissionEnum::GLOBAL_ADMIN->value => ['13800000001'], SuperPermissionEnum::FLOW_ADMIN->value => ['13800000003']], false],
        ];
    }
}
