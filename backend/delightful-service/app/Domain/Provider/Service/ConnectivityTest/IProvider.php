<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ConnectivityTest;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;

/**
 * servicequotientinterface.
 */
interface IProvider
{
    /**
     * 连通propertytest.
     */
    public function connectivityTestByModel(ProviderConfigItem $serviceProviderConfig, string $modelVersion): ConnectResponse;
}
