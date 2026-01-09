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
     * savemodelconfigurationversion（containversionnumber递增andmarkcurrentversioncomplete逻辑）.
     * usetransactionensuredataone致property.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelConfigVersionEntity $entity configurationversion实body
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void;

    /**
     * getfinger定modelmostnewversionID.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int mostnewversionID，ifnot存inthenreturnnull
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int;

    /**
     * getfinger定modelmostnewconfigurationversion实body.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity mostnewversion实body，ifnot存inthenreturnnull
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity;
}
