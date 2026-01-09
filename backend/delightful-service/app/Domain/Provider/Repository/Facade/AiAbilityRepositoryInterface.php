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
 * AI can力仓储interface.
 */
interface AiAbilityRepositoryInterface
{
    /**
     * according tocan力codegetAIcan力实body.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code can力code
     * @return null|AiAbilityEntity AIcan力实body
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): ?AiAbilityEntity;

    /**
     * get所haveAIcan力list.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @return array<AiAbilityEntity> AIcan力实bodylist
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array;

    /**
     * according toIDgetAIcan力实body.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param int $id can力ID
     * @return null|AiAbilityEntity AIcan力实body
     */
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?AiAbilityEntity;

    /**
     * saveAIcan力实body.
     *
     * @param AiAbilityEntity $entity AIcan力实body
     * @return bool whethersavesuccess
     */
    public function save(AiAbilityEntity $entity): bool;

    /**
     * updateAIcan力实body.
     *
     * @param AiAbilityEntity $entity AIcan力实body
     * @return bool whetherupdatesuccess
     */
    public function update(AiAbilityEntity $entity): bool;

    /**
     * according tocodeupdate(supportchoosepropertyupdate).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code can力code
     * @param array $data updatedata(status,configetc)
     * @return bool whetherupdatesuccess
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool;

    /**
     * paginationqueryAIcan力list.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityQuery $query queryitemitem
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array;
}
