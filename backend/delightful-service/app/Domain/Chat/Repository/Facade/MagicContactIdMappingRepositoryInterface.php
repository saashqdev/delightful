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
        DelightfulEnvironmentEntity $magicEnvironmentEntity,
        array $thirdDepartmentIds,
        string $magicOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    /**
     * 获取第三方平台用户ID的映射关系.
     *
     * @param string[] $thirdUserIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdUserIdsMapping(
        DelightfulEnvironmentEntity $magicEnvironmentEntity,
        array $thirdUserIds,
        ?string $magicOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    /**
     * 获取麦吉平台用户ID的映射关系.
     *
     * @param string[] $magicIds
     */
    public function getDelightfulIdsMapping(
        array $magicIds,
        ?string $magicOrganizationCode,
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
        string $magicOrganizationCode,
        PlatformType $thirdPlatformType
    ): array;

    public function getDepartmentRootId(string $magicOrganizationCode, PlatformType $platformType): string;

    /**
     * 获取Delightful部门ID的映射关系.
     */
    public function getDelightfulDepartmentIdsMapping(
        array $magicDepartmentIds,
        string $magicOrganizationCode,
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
        string $magicOrganizationCode,
        PlatformType $thirdPlatformType,
        ThirdPlatformIdMappingType $mappingType
    ): int;
}
