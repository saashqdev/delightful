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
     * get指定model的最新versionID.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int 最新version的ID，如果不存在则returnnull
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int;

    /**
     * get指定model的最新configurationversion实体.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity 最新version的实体，如果不存在则returnnull
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity;
}
