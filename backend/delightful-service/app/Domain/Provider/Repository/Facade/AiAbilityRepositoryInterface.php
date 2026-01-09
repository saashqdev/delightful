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
 * AI cancapabilitystorageinterface.
 */
interface AiAbilityRepositoryInterface
{
    /**
     * according tocancapabilitycodegetAIcanimplementationbody.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code cancapabilitycode
     * @return null|AiAbilityEntity AIcanimplementationbody
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): ?AiAbilityEntity;

    /**
     * get haveAIcancapabilitylist.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @return array<AiAbilityEntity> AIcanimplementationbodylist
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array;

    /**
     * according toIDgetAIcanimplementationbody.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param int $id cancapabilityID
     * @return null|AiAbilityEntity AIcanimplementationbody
     */
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?AiAbilityEntity;

    /**
     * saveAIcanimplementationbody.
     *
     * @param AiAbilityEntity $entity AIcanimplementationbody
     * @return bool whethersavesuccess
     */
    public function save(AiAbilityEntity $entity): bool;

    /**
     * updateAIcanimplementationbody.
     *
     * @param AiAbilityEntity $entity AIcanimplementationbody
     * @return bool whetherupdatesuccess
     */
    public function update(AiAbilityEntity $entity): bool;

    /**
     * according tocodeupdate(supportchoosepropertyupdate).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code cancapabilitycode
     * @param array $data updatedata(status,configetc)
     * @return bool whetherupdatesuccess
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool;

    /**
     * paginationqueryAIcancapabilitylist.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityQuery $query queryitemitem
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array;
}
