<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\Entity\AiAbilityEntity;
use App\Domain\Provider\Entity\ValueObject\AiAbilityCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\AiAbilityQuery;
use App\Infrastructure\Core\ValueObject\Page;

/**
 * AI 能力仓储接口.
 */
interface AiAbilityRepositoryInterface
{
    /**
     * according to能力代码getAI能力实体.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离info
     * @param AiAbilityCode $code 能力代码
     * @return null|AiAbilityEntity AI能力实体
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): ?AiAbilityEntity;

    /**
     * get所有AI能力list.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离info
     * @return array<AiAbilityEntity> AI能力实体list
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array;

    /**
     * according toIDgetAI能力实体.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离info
     * @param int $id 能力ID
     * @return null|AiAbilityEntity AI能力实体
     */
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?AiAbilityEntity;

    /**
     * saveAI能力实体.
     *
     * @param AiAbilityEntity $entity AI能力实体
     * @return bool 是否savesuccess
     */
    public function save(AiAbilityEntity $entity): bool;

    /**
     * updateAI能力实体.
     *
     * @param AiAbilityEntity $entity AI能力实体
     * @return bool 是否updatesuccess
     */
    public function update(AiAbilityEntity $entity): bool;

    /**
     * according tocodeupdate（支持选择性update）.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离info
     * @param AiAbilityCode $code 能力代码
     * @param array $data update数据（status、config等）
     * @return bool 是否updatesuccess
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool;

    /**
     * paginationqueryAI能力list.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离info
     * @param AiAbilityQuery $query query条件
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array;
}
