<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Permission\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Service\RoleDomainService;
use App\Infrastructure\Core\ValueObject\Page;

class RoleAppService extends AbstractKernelAppService
{
    public function __construct(
        private readonly RoleDomainService $roleDomainService
    ) {
    }

    /**
     * queryrolecolumn表.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(PermissionDataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        return $this->roleDomainService->queries($dataIsolation, $page, $filters);
    }

    /**
     * createrole.
     */
    public function createRole(PermissionDataIsolation $dataIsolation, RoleEntity $roleEntity): RoleEntity
    {
        return $this->roleDomainService->save($dataIsolation, $roleEntity);
    }

    /**
     * updaterole.
     */
    public function updateRole(PermissionDataIsolation $dataIsolation, RoleEntity $roleEntity): RoleEntity
    {
        return $this->roleDomainService->save($dataIsolation, $roleEntity);
    }

    /**
     * getroledetail.
     */
    public function show(PermissionDataIsolation $dataIsolation, int $id): RoleEntity
    {
        return $this->roleDomainService->show($dataIsolation, $id);
    }

    /**
     * according tonamegetrole.
     */
    public function getByName(PermissionDataIsolation $dataIsolation, string $name): ?RoleEntity
    {
        return $this->roleDomainService->getByName($dataIsolation, $name);
    }

    /**
     * deleterole.
     */
    public function destroy(PermissionDataIsolation $dataIsolation, int $id): void
    {
        $role = $this->roleDomainService->show($dataIsolation, $id);
        $this->roleDomainService->destroy($dataIsolation, $role);
    }

    /**
     * getuserrolecolumn表.
     */
    public function getUserRoles(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleDomainService->getUserRoles($dataIsolation, $userId);
    }

    /**
     * getuser所havepermission.
     */
    public function getUserPermissions(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleDomainService->getUserPermissions($dataIsolation, $userId);
    }

    /**
     * checkuserwhether拥havefinger定permission.
     */
    public function hasPermission(PermissionDataIsolation $dataIsolation, string $userId, string $permissionKey): bool
    {
        return $this->roleDomainService->hasPermission($dataIsolation, $userId, $permissionKey);
    }

    /**
     * getpermission资源树结构.
     *
     * @param bool $isPlatformOrganization whether平台organization
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        return $this->roleDomainService->getPermissionTree($isPlatformOrganization);
    }
}
