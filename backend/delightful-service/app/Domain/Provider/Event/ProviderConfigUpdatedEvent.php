<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Event;

use App\Domain\Provider\Entity\ProviderConfigEntity;

/**
 * servicequotientconfigurationupdateevent.
 */
class ProviderConfigUpdatedEvent
{
    public function __construct(
        public readonly ProviderConfigEntity $providerConfigEntity,
        public readonly string $organizationCode,
        public readonly string $language,
    ) {
    }
}
