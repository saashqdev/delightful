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
     * 获取第三方平台部门ID的映射关系.
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
     * 获取第三方平台用户ID的映射关系.
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
     * 获取麦吉平台用户ID的映射关系.
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
     * 获取Delightful部门ID的映射关系.
     */
    public function getDelightfulDepartmentIdsMapping(
        array $delightfulDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    public function updateMappingEnvId(int $envId): int;

    /**
     * 根据 origin_id 批量软删除第三方平台映射记录。
     *
     * @param string[] $originIds 第三方平台的原始ID列表
     */
    public function deleteThirdPlatformIdsMapping(
        array $originIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType,
        ThirdPlatformIdMappingType $mappingType
    ): int;
}
