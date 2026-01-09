<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulThirdPlatformIdMappingEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\ThirdPlatformIdMappingType;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

interface DelightfulContactIdMappingRepositoryInterface
{
    /**
     * getthethird-partyplatformdepartmentIDmappingclose系.
     *
     * @param string[] $thirdDepartmentIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdDepartmentIdsMapping(
        DelightfulEnvironmentEntity $delightfulEnvironmentEntity,
        array $thirdDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    /**
     * getthethird-partyplatformuserIDmappingclose系.
     *
     * @param string[] $thirdUserIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdUserIdsMapping(
        DelightfulEnvironmentEntity $delightfulEnvironmentEntity,
        array $thirdUserIds,
        ?string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    /**
     * getMagicplatformuserIDmappingclose系.
     *
     * @param string[] $delightfulIds
     */
    public function getDelightfulIdsMapping(
        array $delightfulIds,
        ?string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    /**
     * @param DelightfulThirdPlatformIdMappingEntity[] $thirdPlatformIdMappingEntities
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function createThirdPlatformIdsMapping(array $thirdPlatformIdMappingEntities): array;

    /**
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdDepartments(
        array $currentDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    public function getDepartmentRootId(string $delightfulOrganizationCode, PlatformType $platformType): string;

    /**
     * getDelightfuldepartmentIDmappingclose系.
     */
    public function getDelightfulDepartmentIdsMapping(
        array $delightfulDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    public function updateMappingEnvId(int $envId): int;

    /**
     * according to origin_id batchquantitysoftdeletethethird-partyplatformmappingrecord.
     *
     * @param string[] $originIds thethird-partyplatformoriginalIDcolumntable
     */
    public function deleteThirdPlatformIdsMapping(
        array $originIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType,
        ThirdPlatformIdMappingType $mappingType
    ): int;
}
