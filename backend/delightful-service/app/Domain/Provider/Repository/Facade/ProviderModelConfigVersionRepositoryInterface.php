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
     * savemodelconfigurationversion（containversion号递增和markcurrentversion的完整逻辑）.
     * usetransactionensuredata一致性.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelConfigVersionEntity $entity configurationversion实体
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void;

    /**
     * get指定model的most新versionID.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int most新version的ID，ifnot存inthenreturnnull
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int;

    /**
     * get指定model的most新configurationversion实体.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity most新version的实体，ifnot存inthenreturnnull
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity;
}
