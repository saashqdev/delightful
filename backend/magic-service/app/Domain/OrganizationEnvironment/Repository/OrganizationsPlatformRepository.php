<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository;

use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsPlatformRepositoryInterface;

class OrganizationsPlatformRepository implements OrganizationsPlatformRepositoryInterface
{
    public function getOrganizationPlatformType(string $magicOrganizationCode): PlatformType
    {
        return PlatformType::Magic;
    }
}
