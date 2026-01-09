<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Contract;

/**
 * permission枚举接口
 * 提供permission管理的统一抽象
 */
interface DelightfulPermissionInterface
{
    /**
     * get所有操作type.
     */
    public function getOperations(): array;

    /**
     * get所有资源.
     */
    public function getResources(): array;

    /**
     * buildpermission标识.
     */
    public function buildPermission(string $resource, string $operation): string;

    /**
     * 解析permission标识.
     */
    public function parsePermission(string $permissionKey): array;

    /**
     * generate所有可能的permission组合.
     */
    public function generateAllPermissions(): array;

    /**
     * getpermission树结构.
     *
     * @param bool $isPlatformOrganization 是否平台organization，平台organization下才contain platform 平台的资源树
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array;

    /**
     * checkpermission键是否valid.
     */
    public function isValidPermission(string $permissionKey): bool;

    /**
     * get资源tag.
     */
    public function getResourceLabel(string $resource): string;

    /**
     * get操作tag.
     */
    public function getOperationLabel(string $operation): string;

    /**
     * get资源的模块.
     */
    public function getResourceModule(string $resource): string;

    /**
     * checkuserpermission集合中是否contain指定permission（考虑隐式contain）。
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool;
}
