<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Contract;

/**
 * permission枚举interface
 * providepermissionmanage统oneabstract
 */
interface DelightfulPermissionInterface
{
    /**
     * get所have操astype.
     */
    public function getOperations(): array;

    /**
     * get所haveresource.
     */
    public function getResources(): array;

    /**
     * buildpermissionidentifier.
     */
    public function buildPermission(string $resource, string $operation): string;

    /**
     * parsepermissionidentifier.
     */
    public function parsePermission(string $permissionKey): array;

    /**
     * generate所havemaybepermissiongroup合.
     */
    public function generateAllPermissions(): array;

    /**
     * getpermissiontree结构.
     *
     * @param bool $isPlatformOrganization whether平台organization,平台organizationdown才contain platform 平台resourcetree
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array;

    /**
     * checkpermissionkeywhethervalid.
     */
    public function isValidPermission(string $permissionKey): bool;

    /**
     * getresourcetag.
     */
    public function getResourceLabel(string $resource): string;

    /**
     * get操astag.
     */
    public function getOperationLabel(string $operation): string;

    /**
     * getresource模piece.
     */
    public function getResourceModule(string $resource): string;

    /**
     * checkuserpermissionsetmiddlewhethercontainfinger定permission(考虑隐typecontain).
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool;
}
