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
     * checkhand机numberwhetherhavepermissionaccessfinger定permission.
     *
     * @param string $mobile hand机number
     * @param SuperPermissionEnum $permissionEnum wantcheckpermissiontype
     * @return bool whetherhavepermission
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
     * inside部permissioncheckmethod,便attest.
     *
     * @param string $mobile hand机number
     * @param SuperPermissionEnum $permission wantcheckpermission
     * @param array $permissions permissionconfiguration
     * @return bool whetherhavepermission
     */
    public static function checkPermission(
        string $mobile,
        SuperPermissionEnum $permission,
        array $permissions
    ): bool {
        if (empty($mobile)) {
            return false;
        }

        // judgewhetherall局administrator
        $globalAdminsEnum = SuperPermissionEnum::GLOBAL_ADMIN->value;
        if (isset($permissions[$globalAdminsEnum]) && in_array($mobile, $permissions[$globalAdminsEnum])) {
            return true;
        }

        // judgewhether特定permission
        $permissionKey = $permission->value;
        return isset($permissions[$permissionKey]) && in_array($mobile, $permissions[$permissionKey]);
    }

    public static function isOrganizationAdmin(string $organizationCode, string $mobile): bool
    {
        $permission = di(PermissionInterface::class);
        return $permission->isOrganizationAdmin($organizationCode, $mobile);
    }

    /**
     * getuser拥haveadministratorpermissionorganizationencodinglist.
     */
    public static function getUserOrganizationAdminList(string $mageId): array
    {
        $permission = di(PermissionInterface::class);
        return $permission->getOrganizationAdminList($mageId);
    }
}
