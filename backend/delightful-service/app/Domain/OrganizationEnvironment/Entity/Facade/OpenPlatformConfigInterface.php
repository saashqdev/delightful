<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\Facade;

/**
 * open放platform所haveconfigurationsaveindatabaseonefieldmiddle.
 */
interface OpenPlatformConfigInterface
{
    public function initObject(array $data): static;

    public function toArray(): array;
}
