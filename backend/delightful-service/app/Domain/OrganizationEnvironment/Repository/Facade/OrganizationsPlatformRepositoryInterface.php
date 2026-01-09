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
     * getorganization所属(thethird-party)平台.
     * 麦吉supportfrom其他平台同organization架构, 所byneed知道organization所属平台.
     */
    public function getOrganizationPlatformType(string $delightfulOrganizationCode): PlatformType;
}
