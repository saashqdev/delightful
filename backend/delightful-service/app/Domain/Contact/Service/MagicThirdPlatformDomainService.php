<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Chat\Repository\Persistence\DelightfulContactIdMappingRepository;
use App\Domain\Contact\Entity\DelightfulThirdPlatformIdMappingEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;

readonly class DelightfulThirdPlatformDomainService
{
    public function __construct(private DelightfulContactIdMappingRepository $thirdPlatformRepository)
    {
    }

    /**
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdDepartments(
        array $currentDepartmentIds,
        string $magicOrganizationCode,
        PlatformType $thirdPlatformType
    ): array {
        return $this->thirdPlatformRepository->getThirdDepartments($currentDepartmentIds, $magicOrganizationCode, $thirdPlatformType);
    }
}
