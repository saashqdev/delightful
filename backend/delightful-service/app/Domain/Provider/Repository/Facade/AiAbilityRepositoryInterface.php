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
 * AI 能力仓储interface.
 */
interface AiAbilityRepositoryInterface
{
    /**
     * according to能力codegetAI能力实body.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityCode $code 能力code
     * @return null|AiAbilityEntity AI能力实body
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): ?AiAbilityEntity;

    /**
     * get所haveAI能力list.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @return array<AiAbilityEntity> AI能力实bodylist
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array;

    /**
     * according toIDgetAI能力实body.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param int $id 能力ID
     * @return null|AiAbilityEntity AI能力实body
     */
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?AiAbilityEntity;

    /**
     * saveAI能力实body.
     *
     * @param AiAbilityEntity $entity AI能力实body
     * @return bool whethersavesuccess
     */
    public function save(AiAbilityEntity $entity): bool;

    /**
     * updateAI能力实body.
     *
     * @param AiAbilityEntity $entity AI能力实body
     * @return bool whetherupdatesuccess
     */
    public function update(AiAbilityEntity $entity): bool;

    /**
     * according tocodeupdate（support选择propertyupdate）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityCode $code 能力code
     * @param array $data updatedata（status、configetc）
     * @return bool whetherupdatesuccess
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool;

    /**
     * paginationqueryAI能力list.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityQuery $query queryitemitem
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array;
}
