<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Contract;

/**
 * permission枚举interface
 * 提供permission管理的统一抽象
 */
interface DelightfulPermissionInterface
{
    /**
     * get所have操作type.
     */
    public function getOperations(): array;

    /**
     * get所haveresource.
     */
    public function getResources(): array;

    /**
     * buildpermission标识.
     */
    public function buildPermission(string $resource, string $operation): string;

    /**
     * parsepermission标识.
     */
    public function parsePermission(string $permissionKey): array;

    /**
     * generate所have可能的permissiongroup合.
     */
    public function generateAllPermissions(): array;

    /**
     * getpermissiontree结构.
     *
     * @param bool $isPlatformOrganization whether平台organization，平台organizationdown才contain platform 平台的resourcetree
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array;

    /**
     * checkpermission键whethervalid.
     */
    public function isValidPermission(string $permissionKey): bool;

    /**
     * getresourcetag.
     */
    public function getResourceLabel(string $resource): string;

    /**
     * get操作tag.
     */
    public function getOperationLabel(string $operation): string;

    /**
     * getresource的模piece.
     */
    public function getResourceModule(string $resource): string;

    /**
     * checkuserpermissionsetmiddlewhethercontainfinger定permission（考虑隐typecontain）。
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool;
}
