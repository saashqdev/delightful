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
     * saveorganizationadministrator.
     */
    public function save(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): OrganizationAdminEntity;

    /**
     * according toIDgetorganizationadministrator.
     */
    public function getById(DataIsolation $dataIsolation, int $id): ?OrganizationAdminEntity;

    /**
     * according touserIDgetorganizationadministrator.
     */
    public function getByUserId(DataIsolation $dataIsolation, string $userId): ?OrganizationAdminEntity;

    /**
     * queryorganizationadministratorlist.
     * @return array{total: int, list: OrganizationAdminEntity[]}
     */
    public function queries(DataIsolation $dataIsolation, Page $page, ?array $filters = null): array;

    /**
     * deleteorganizationadministrator.
     */
    public function delete(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): void;

    /**
     * checkuserwhetherfororganizationadministrator.
     */
    public function isOrganizationAdmin(DataIsolation $dataIsolation, string $userId): bool;

    /**
     * 授予userorganizationadministratorpermission.
     */
    public function grant(DataIsolation $dataIsolation, string $userId, ?string $grantorUserId, ?string $remarks = null, bool $isOrganizationCreator = false): OrganizationAdminEntity;

    /**
     * undouserorganizationadministratorpermission.
     */
    public function revoke(DataIsolation $dataIsolation, string $userId): void;

    /**
     * getorganizationcreateperson.
     */
    public function getOrganizationCreator(DataIsolation $dataIsolation): ?OrganizationAdminEntity;

    /**
     * getorganizationdown所haveorganizationadministrator.
     */
    public function getAllOrganizationAdmins(DataIsolation $dataIsolation): array;

    /**
     * 批quantitycheckuserwhetherfororganizationadministrator.
     */
    public function batchCheckOrganizationAdmin(DataIsolation $dataIsolation, array $userIds): array;
}
