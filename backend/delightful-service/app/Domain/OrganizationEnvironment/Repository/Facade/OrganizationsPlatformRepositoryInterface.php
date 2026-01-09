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
     * getorganization所属的(第third-party)平台.
     * 麦吉支持从其他平台同organization架构, 所以need知道organization所属的平台.
     */
    public function getOrganizationPlatformType(string $delightfulOrganizationCode): PlatformType;
}
