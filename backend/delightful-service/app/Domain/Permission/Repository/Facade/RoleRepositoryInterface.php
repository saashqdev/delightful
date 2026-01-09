<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Repository\Facade;

use App\Domain\Permission\Entity\RoleEntity;
use App\Infrastructure\Core\ValueObject\Page;

interface RoleRepositoryInterface
{
    /**
     * 保存角色.
     */
    public function save(string $organizationCode, RoleEntity $roleEntity): RoleEntity;

    /**
     * 根据IDget角色.
     */
    public function getById(string $organizationCode, int $id): ?RoleEntity;

    /**
     * 根据名称get角色.
     */
    public function getByName(string $organizationCode, string $name): ?RoleEntity;

    /**
     * query角色列表.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(string $organizationCode, Page $page, ?array $filters = null): array;

    /**
     * delete角色.
     */
    public function delete(string $organizationCode, RoleEntity $roleEntity): void;

    /**
     * 为角色分配user.
     */
    public function assignUsers(string $organizationCode, int $roleId, array $userIds, ?string $assignedBy = null): void;

    /**
     * 移除角色user.
     */
    public function removeUsers(string $organizationCode, int $roleId, array $userIds): void;

    /**
     * get角色的user列表.
     */
    public function getRoleUsers(string $organizationCode, int $roleId): array;

    /**
     * 批量get多个角色的user列表。
     * return格式为 [roleId => userId[]].
     *
     * @param string $organizationCode organization编码
     * @param int[] $roleIds 角色 ID 列表
     *
     * @return array<int, array>
     */
    public function getRoleUsersMap(string $organizationCode, array $roleIds): array;

    /**
     * getuser的角色列表.
     */
    public function getUserRoles(string $organizationCode, string $userId): array;

    /**
     * getuser的所有permission.
     */
    public function getUserPermissions(string $organizationCode, string $userId): array;
}
