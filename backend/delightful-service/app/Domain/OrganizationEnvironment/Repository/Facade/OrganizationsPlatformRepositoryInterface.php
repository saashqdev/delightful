<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Facade;

use App\Domain\Contact\Entity\ValueObject\PlatformType;

interface OrganizationsPlatformRepositoryInterface
{
    /**
     * getorganizationbelong to(thethird-party)platform.
     * Magicsupportfromotherplatformsameorganizationarchitecture,  byneedknoworganizationbelong toplatform.
     */
    public function getOrganizationPlatformType(string $delightfulOrganizationCode): PlatformType;
}
