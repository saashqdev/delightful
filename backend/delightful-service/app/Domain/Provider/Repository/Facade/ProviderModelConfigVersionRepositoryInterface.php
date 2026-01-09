<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\Entity\ProviderModelConfigVersionEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;

interface ProviderModelConfigVersionRepositoryInterface
{
    /**
     * savemodelconfigurationversion(containversionnumberincrementandmarkcurrentversioncompletelogic).
     * usetransactionensuredataonetoproperty.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param ProviderModelConfigVersionEntity $entity configurationversionactualbody
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void;

    /**
     * getfingersetmodelmostnewversionID.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param int $serviceProviderModelId modelID
     * @return null|int mostnewversionID,ifnotexistsinthenreturnnull
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int;

    /**
     * getfingersetmodelmostnewconfigurationversionactualbody.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity mostnewversionactualbody,ifnotexistsinthenreturnnull
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity;
}
