<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Repository\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Permission\Entity\OrganizationAdminEntity;
use App\Infrastructure\Core\ValueObject\Page;

interface OrganizationAdminRepositoryInterface
{
    /**
     * 保存organization管理员.
     */
    public function save(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): OrganizationAdminEntity;

    /**
     * according toIDgetorganization管理员.
     */
    public function getById(DataIsolation $dataIsolation, int $id): ?OrganizationAdminEntity;

    /**
     * according touserIDgetorganization管理员.
     */
    public function getByUserId(DataIsolation $dataIsolation, string $userId): ?OrganizationAdminEntity;

    /**
     * queryorganization管理员list.
     * @return array{total: int, list: OrganizationAdminEntity[]}
     */
    public function queries(DataIsolation $dataIsolation, Page $page, ?array $filters = null): array;

    /**
     * deleteorganization管理员.
     */
    public function delete(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): void;

    /**
     * checkuser是否为organization管理员.
     */
    public function isOrganizationAdmin(DataIsolation $dataIsolation, string $userId): bool;

    /**
     * 授予userorganization管理员permission.
     */
    public function grant(DataIsolation $dataIsolation, string $userId, ?string $grantorUserId, ?string $remarks = null, bool $isOrganizationCreator = false): OrganizationAdminEntity;

    /**
     * 撤销userorganization管理员permission.
     */
    public function revoke(DataIsolation $dataIsolation, string $userId): void;

    /**
     * getorganizationcreate人.
     */
    public function getOrganizationCreator(DataIsolation $dataIsolation): ?OrganizationAdminEntity;

    /**
     * getorganization下所有organization管理员.
     */
    public function getAllOrganizationAdmins(DataIsolation $dataIsolation): array;

    /**
     * 批量checkuser是否为organization管理员.
     */
    public function batchCheckOrganizationAdmin(DataIsolation $dataIsolation, array $userIds): array;
}
