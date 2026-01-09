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
     * getorganization所属(thethird-party)platform.
     * 麦吉supportfromotherplatform同organization架构, 所byneedknoworganization所属platform.
     */
    public function getOrganizationPlatformType(string $delightfulOrganizationCode): PlatformType;
}
