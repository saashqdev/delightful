<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Contract;

/**
 * permissionenuminterface
 * providepermissionmanagesystemoneabstract
 */
interface DelightfulPermissionInterface
{
    /**
     * get haveoperationastype.
     */
    public function getOperations(): array;

    /**
     * get haveresource.
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
     * generate havemaybepermissiongroupcombine.
     */
    public function generateAllPermissions(): array;

    /**
     * getpermissiontreestructure.
     *
     * @param bool $isPlatformOrganization whetherplatformorganization,platformorganizationdownonlycontain platform platformresourcetree
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
     * getoperationastag.
     */
    public function getOperationLabel(string $operation): string;

    /**
     * getresourcemodepiece.
     */
    public function getResourceModule(string $resource): string;

    /**
     * checkuserpermissionsetmiddlewhethercontainfingersetpermission(considerhiddentypecontain).
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool;
}
