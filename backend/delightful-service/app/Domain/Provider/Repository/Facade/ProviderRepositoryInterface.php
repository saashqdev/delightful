<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface ProviderRepositoryInterface
{
    public function getById(int $id): ?ProviderEntity;

    /**
     * @param array<int> $ids
     * @return array<int, ProviderEntity> returnbyidforkeyactualbodyobjectarray
     */
    public function getByIds(array $ids): array;

    /**
     * @return array{total: int, list: array<ProviderEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderQuery $query, Page $page): array;

    public function getAllNonOfficialProviders(Category $category): array;

    /**
     * according tocategoryget haveservicequotient.
     * @param Category $category category
     * @return ProviderEntity[] servicequotientactualbodylist
     */
    public function getByCategory(Category $category): array;

    /**
     * according toProviderCodeandCategorygetservicequotient.
     * @param ProviderCode $providerCode servicequotientencoding
     * @param Category $category category
     * @return null|ProviderEntity servicequotientactualbody
     */
    public function getByCodeAndCategory(ProviderCode $providerCode, Category $category): ?ProviderEntity;

    /**
     * according toIDgetservicequotientactualbody(notbyorganizationfilter,alllocalquery).
     *
     * @param int $id servicequotientID
     * @return null|ProviderEntity servicequotientactualbody
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderEntity;

    /**
     * according toIDarraygetservicequotientactualbodylist(notbyorganizationfilter,alllocalquery).
     *
     * @param array<int> $ids servicequotientIDarray
     * @return array<int, ProviderEntity> returnbyidforkeyservicequotientactualbodyarray
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;
}
