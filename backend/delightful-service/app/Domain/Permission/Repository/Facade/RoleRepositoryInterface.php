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
     * saverole.
     */
    public function save(string $organizationCode, RoleEntity $roleEntity): RoleEntity;

    /**
     * according toIDgetrole.
     */
    public function getById(string $organizationCode, int $id): ?RoleEntity;

    /**
     * according tonamegetrole.
     */
    public function getByName(string $organizationCode, string $name): ?RoleEntity;

    /**
     * queryrolecolumntable.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(string $organizationCode, Page $page, ?array $filters = null): array;

    /**
     * deleterole.
     */
    public function delete(string $organizationCode, RoleEntity $roleEntity): void;

    /**
     * forroleminute配user.
     */
    public function assignUsers(string $organizationCode, int $roleId, array $userIds, ?string $assignedBy = null): void;

    /**
     * 移exceptroleuser.
     */
    public function removeUsers(string $organizationCode, int $roleId, array $userIds): void;

    /**
     * getroleusercolumntable.
     */
    public function getRoleUsers(string $organizationCode, int $roleId): array;

    /**
     * batchquantityget多roleusercolumntable.
     * returnformatfor [roleId => userId[]].
     *
     * @param string $organizationCode organizationencoding
     * @param int[] $roleIds role ID columntable
     *
     * @return array<int, array>
     */
    public function getRoleUsersMap(string $organizationCode, array $roleIds): array;

    /**
     * getuserrolecolumntable.
     */
    public function getUserRoles(string $organizationCode, string $userId): array;

    /**
     * getuser所havepermission.
     */
    public function getUserPermissions(string $organizationCode, string $userId): array;
}
