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
     * @return array<int, ProviderEntity> returnbyidforkey实bodyobjectarray
     */
    public function getByIds(array $ids): array;

    /**
     * @return array{total: int, list: array<ProviderEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderQuery $query, Page $page): array;

    public function getAllNonOfficialProviders(Category $category): array;

    /**
     * according tocategoryget所haveservicequotient.
     * @param Category $category category
     * @return ProviderEntity[] servicequotient实bodylist
     */
    public function getByCategory(Category $category): array;

    /**
     * according toProviderCodeandCategorygetservicequotient.
     * @param ProviderCode $providerCode servicequotientencoding
     * @param Category $category category
     * @return null|ProviderEntity servicequotient实body
     */
    public function getByCodeAndCategory(ProviderCode $providerCode, Category $category): ?ProviderEntity;

    /**
     * according toIDgetservicequotient实body（not按organizationfilter，all局query）.
     *
     * @param int $id servicequotientID
     * @return null|ProviderEntity servicequotient实body
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderEntity;

    /**
     * according toIDarraygetservicequotient实bodylist（not按organizationfilter，all局query）.
     *
     * @param array<int> $ids servicequotientIDarray
     * @return array<int, ProviderEntity> returnbyidforkeyservicequotient实bodyarray
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;
}
