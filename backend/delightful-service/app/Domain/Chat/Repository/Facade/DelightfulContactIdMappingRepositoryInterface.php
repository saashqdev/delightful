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
     * get第third-party平台departmentID的映射关系.
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
     * get第third-party平台userID的映射关系.
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
     * get麦吉平台userID的映射关系.
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
     * getDelightfuldepartmentID的映射关系.
     */
    public function getDelightfulDepartmentIdsMapping(
        array $delightfulDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    public function updateMappingEnvId(int $envId): int;

    /**
     * according to origin_id 批量软delete第third-party平台映射记录。
     *
     * @param string[] $originIds 第third-party平台的originalID列表
     */
    public function deleteThirdPlatformIdsMapping(
        array $originIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType,
        ThirdPlatformIdMappingType $mappingType
    ): int;
}
