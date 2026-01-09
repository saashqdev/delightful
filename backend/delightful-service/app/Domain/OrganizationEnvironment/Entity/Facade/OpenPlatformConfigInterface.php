<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\Facade;

/**
 * 开放平台的所haveconfigurationsaveindatabase的onefieldmiddle.
 */
interface OpenPlatformConfigInterface
{
    public function initObject(array $data): static;

    public function toArray(): array;
}
