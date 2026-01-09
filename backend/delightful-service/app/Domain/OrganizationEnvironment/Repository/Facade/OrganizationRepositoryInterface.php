<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Facade;

use App\Domain\OrganizationEnvironment\Entity\OrganizationEntity;
use App\Infrastructure\Core\ValueObject\Page;

/**
 * organizationwarehouselibraryinterface.
 */
interface OrganizationRepositoryInterface
{
    /**
     * saveorganization.
     */
    public function save(OrganizationEntity $organizationEntity): OrganizationEntity;

    /**
     * according toIDgetorganization.
     */
    public function getById(int $id): ?OrganizationEntity;

    /**
     * according toencodinggetorganization.
     */
    public function getByCode(string $code): ?OrganizationEntity;

    /**
     * according toencodingcolumntablebatchquantitygetorganization.
     * @param string[] $codes
     * @return OrganizationEntity[]
     */
    public function getByCodes(array $codes): array;

    /**
     * according tonamegetorganization.
     */
    public function getByName(string $name): ?OrganizationEntity;

    /**
     * queryorganizationcolumntable.
     * @return array{total: int, list: OrganizationEntity[]}
     */
    public function queries(Page $page, ?array $filters = null): array;

    /**
     * deleteorganization.
     */
    public function delete(OrganizationEntity $organizationEntity): void;

    /**
     * checkencodingwhetheralreadyexistsin.
     */
    public function existsByCode(string $code, ?int $excludeId = null): bool;
}
