<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Event;

use App\Domain\Provider\Entity\ProviderModelEntity;

/**
 * 服务商modelcreateevent.
 */
class ProviderModelCreatedEvent
{
    public function __construct(
        public readonly ProviderModelEntity $providerModelEntity,
        public readonly string $organizationCode
    ) {
    }
}
