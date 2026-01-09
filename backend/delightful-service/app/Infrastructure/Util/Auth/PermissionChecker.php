<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth;

use App\Application\Kernel\SuperPermissionEnum;
use App\Infrastructure\Util\Auth\Permission\PermissionInterface;

class PermissionChecker
{
    /**
     * check手机号是否有permissionaccess指定的permission.
     *
     * @param string $mobile 手机号
     * @param SuperPermissionEnum $permissionEnum 要check的permissiontype
     * @return bool 是否有permission
     */
    public static function mobileHasPermission(string $mobile, SuperPermissionEnum $permissionEnum): bool
    {
        if (empty($mobile)) {
            return false;
        }
        // getpermissionconfiguration
        $permissions = \Hyperf\Config\config('permission.super_whitelists', []);
        return self::checkPermission($mobile, $permissionEnum, $permissions);
    }

    /**
     * 内部permissioncheckmethod，便于test.
     *
     * @param string $mobile 手机号
     * @param SuperPermissionEnum $permission 要check的permission
     * @param array $permissions permissionconfiguration
     * @return bool 是否有permission
     */
    public static function checkPermission(
        string $mobile,
        SuperPermissionEnum $permission,
        array $permissions
    ): bool {
        if (empty($mobile)) {
            return false;
        }

        // 判断是否全局管理员
        $globalAdminsEnum = SuperPermissionEnum::GLOBAL_ADMIN->value;
        if (isset($permissions[$globalAdminsEnum]) && in_array($mobile, $permissions[$globalAdminsEnum])) {
            return true;
        }

        // 判断是否特定permission
        $permissionKey = $permission->value;
        return isset($permissions[$permissionKey]) && in_array($mobile, $permissions[$permissionKey]);
    }

    public static function isOrganizationAdmin(string $organizationCode, string $mobile): bool
    {
        $permission = di(PermissionInterface::class);
        return $permission->isOrganizationAdmin($organizationCode, $mobile);
    }

    /**
     * getuser拥有管理员permission的organizationencodinglist.
     */
    public static function getUserOrganizationAdminList(string $mageId): array
    {
        $permission = di(PermissionInterface::class);
        return $permission->getOrganizationAdminList($mageId);
    }
}
