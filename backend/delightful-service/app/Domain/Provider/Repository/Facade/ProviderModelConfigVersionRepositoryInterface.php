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
     * savemodelconfiguration版本（包含版本号递增和mark当前版本的完整逻辑）.
     * use事务确保数据一致性.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param ProviderModelConfigVersionEntity $entity configuration版本实体
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void;

    /**
     * get指定model的最新版本ID.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int 最新版本的ID，如果不存在则returnnull
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int;

    /**
     * get指定model的最新configuration版本实体.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity 最新版本的实体，如果不存在则returnnull
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity;
}
