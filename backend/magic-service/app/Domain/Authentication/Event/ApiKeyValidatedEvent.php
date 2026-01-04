<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Authentication\Event;

use App\Domain\Authentication\Entity\ApiKeyProviderEntity;

readonly class ApiKeyValidatedEvent
{
    public function __construct(
        protected ApiKeyProviderEntity $apiKeyProvider,
    ) {
    }

    public function getApiKeyProvider(): ApiKeyProviderEntity
    {
        return $this->apiKeyProvider;
    }
}
