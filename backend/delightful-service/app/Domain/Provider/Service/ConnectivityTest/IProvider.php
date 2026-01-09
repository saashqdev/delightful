<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ConnectivityTest;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;

/**
 * 服务商interface.
 */
interface IProvider
{
    /**
     * 连通性test.
     */
    public function connectivityTestByModel(ProviderConfigItem $serviceProviderConfig, string $modelVersion): ConnectResponse;
}
