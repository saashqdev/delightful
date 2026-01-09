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
     * @return array<int, ProviderEntity> return以id为key的实体objectarray
     */
    public function getByIds(array $ids): array;

    /**
     * @return array{total: int, list: array<ProviderEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderQuery $query, Page $page): array;

    public function getAllNonOfficialProviders(Category $category): array;

    /**
     * according to分类get所有service商.
     * @param Category $category 分类
     * @return ProviderEntity[] service商实体list
     */
    public function getByCategory(Category $category): array;

    /**
     * according toProviderCode和Categorygetservice商.
     * @param ProviderCode $providerCode service商编码
     * @param Category $category 分类
     * @return null|ProviderEntity service商实体
     */
    public function getByCodeAndCategory(ProviderCode $providerCode, Category $category): ?ProviderEntity;

    /**
     * according toIDgetservice商实体（不按organizationfilter，全局query）.
     *
     * @param int $id service商ID
     * @return null|ProviderEntity service商实体
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderEntity;

    /**
     * according toIDarraygetservice商实体list（不按organizationfilter，全局query）.
     *
     * @param array<int> $ids service商IDarray
     * @return array<int, ProviderEntity> return以id为key的service商实体array
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;
}
