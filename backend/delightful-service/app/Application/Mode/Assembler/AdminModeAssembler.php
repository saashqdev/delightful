<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\Assembler;

use App\Application\Mode\DTO\Admin\AdminModeAggregateDTO;
use App\Application\Mode\DTO\Admin\AdminModeDTO;
use App\Application\Mode\DTO\Admin\AdminModeGroupAggregateDTO;
use App\Application\Mode\DTO\Admin\AdminModeGroupDTO;
use App\Application\Mode\DTO\ModeGroupModelDTO;
use App\Application\Mode\DTO\ModeGroupRelationDTO;
use App\Application\Mode\DTO\ValueObject\ModelStatus;
use App\Domain\Mode\Entity\ModeAggregate;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\ModeGroupAggregate;
use App\Domain\Mode\Entity\ModeGroupEntity;
use App\Domain\Mode\Entity\ModeGroupRelationEntity;
use App\Interfaces\Mode\DTO\Request\CreateModeGroupRequest;
use App\Interfaces\Mode\DTO\Request\CreateModeRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeGroupRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeRequest;
use Hyperf\Contract\TranslatorInterface;

class AdminModeAssembler
{
    /**
     * 实体转换为管理后台DTO (contain完整的i18nfield).
     */
    public static function modeToAdminDTO(ModeEntity $entity): AdminModeDTO
    {
        $data = $entity->toArray();
        return new AdminModeDTO($data);
    }

    public static function groupEntityToAdminDTO(ModeGroupEntity $entity): AdminModeGroupDTO
    {
        return new AdminModeGroupDTO($entity->toArray());
    }

    /**
     * 关联实体转换为DTO.
     */
    public static function relationEntityToDTO(ModeGroupRelationEntity $entity): ModeGroupRelationDTO
    {
        return new ModeGroupRelationDTO($entity->toArray());
    }

    /**
     * 聚合根转换为DTO.
     *
     * @param ModeAggregate $aggregate 模式聚合根
     * @param array $providerModels optional的modelinfo映射 [modelId => ProviderModelEntity]
     */
    public static function aggregateToAdminDTO(ModeAggregate $aggregate, array $providerModels = []): AdminModeAggregateDTO
    {
        $dto = new AdminModeAggregateDTO();
        $dto->setMode(self::modeToAdminDTO($aggregate->getMode()));

        $groupAggregatesDTOs = array_map(
            fn ($groupAggregate) => self::groupAggregateToAdminDTO($groupAggregate, $providerModels),
            $aggregate->getGroupAggregates()
        );

        $dto->setGroups($groupAggregatesDTOs);

        return $dto;
    }

    /**
     * 分组聚合根转换为DTO.
     *
     * @param ModeGroupAggregate $groupAggregate 分组聚合根
     * @param array $providerModels optional的modelinfo映射 [model_id => ['best' => ProviderModelEntity|null, 'all' => ProviderModelEntity[], 'status' => string]]
     */
    public static function groupAggregateToAdminDTO(ModeGroupAggregate $groupAggregate, array $providerModels = []): AdminModeGroupAggregateDTO
    {
        $dto = new AdminModeGroupAggregateDTO();
        $dto->setGroup(self::groupEntityToAdminDTO($groupAggregate->getGroup()));
        $locale = di(TranslatorInterface::class)->getLocale();

        $models = [];
        foreach ($groupAggregate->getRelations() as $relation) {
            $modelDTO = new ModeGroupModelDTO($relation->toArray());

            // use model_id 查找model
            $modelId = $relation->getModelId();
            $modelInfo = $providerModels[$modelId] ?? null;

            if ($modelInfo && $modelInfo['best']) {
                // 找到可用model，use最佳model的info
                $providerModel = $modelInfo['best'];
                $modelDTO->setModelName($providerModel->getName());
                $modelDTO->setModelIcon($providerModel->getIcon());
                $modelDTO->setModelStatus($modelInfo['status']); // use计算出的status
                $description = '';
                $translate = $providerModel->getTranslate();
                if (is_array($translate) && isset($translate['description'][$locale])) {
                    $description = $translate['description'][$locale];
                } else {
                    $description = $providerModel->getDescription();
                }
                $modelDTO->setModelDescription($description);

                // 保持向后compatible，set providerModelId 为查找到的model的ID
                $modelDTO->setProviderModelId((string) $providerModel->getId());
            } else {
                // 后台管理need显示所有status，include无可用model的情况
                $status = $modelInfo['status'] ?? ModelStatus::Deleted;
                $modelDTO->setModelStatus($status);
                $modelDTO->setModelStatus($status);
            }

            $models[] = $modelDTO;
        }

        $dto->setModels($models);

        return $dto;
    }

    /**
     * 实体array转换为管理后台DTOarray.
     */
    public static function entitiesToAdminDTOs(array $entities): array
    {
        return array_map(fn ($entity) => self::modeToAdminDTO($entity), $entities);
    }

    /**
     * 分组实体array转换为管理后台DTOarray.
     */
    public static function groupEntitiesToAdminDTOs(array $entities): array
    {
        return array_map(fn ($entity) => self::groupEntityToAdminDTO($entity), $entities);
    }

    /**
     * 关联实体array转换为DTOarray.
     */
    public static function relationEntitiesToDTOs(array $entities): array
    {
        return array_map(fn ($entity) => self::relationEntityToDTO($entity), $entities);
    }

    public static function modelDTOToEntity(AdminModeDTO $modeDTO)
    {
        return new ModeEntity($modeDTO->toArray());
    }

    /**
     * ModeAggregateDTO转换为ModeAggregate实体.
     */
    public static function aggregateDTOToEntity(AdminModeAggregateDTO $dto): ModeAggregate
    {
        $mode = self::modelDTOToEntity($dto->getMode());

        $groupAggregates = array_map(
            fn ($groupAggregateDTO) => self::groupAggregateDTOToEntity($groupAggregateDTO),
            $dto->getGroups()
        );

        return new ModeAggregate($mode, $groupAggregates);
    }

    /**
     * ModeGroupAggregateDTO转换为ModeGroupAggregate实体.
     */
    public static function groupAggregateDTOToEntity(AdminModeGroupAggregateDTO $dto): ModeGroupAggregate
    {
        $group = self::groupDTOToEntity($dto->getGroup());
        $relations = [];
        foreach ($dto->getModels() as $model) {
            $relation = new ModeGroupRelationEntity($model);
            $relation->setModeId($group->getModeId());
            $relation->setGroupId($group->getId());
            $relations[] = $relation;
        }

        return new ModeGroupAggregate($group, $relations);
    }

    /**
     * ModeGroupDTO转换为ModeGroupEntity实体.
     */
    public static function groupDTOToEntity(AdminModeGroupDTO $dto): ModeGroupEntity
    {
        return new ModeGroupEntity($dto->toArray());
    }

    /**
     * CreateModeRequest转换为ModeEntity.
     */
    public static function createModeRequestToEntity(CreateModeRequest $request): ModeEntity
    {
        return new ModeEntity($request->all());
    }

    /**
     * 将UpdateModeRequest的dataapplication到现有ModeEntity（部分update）.
     */
    public static function applyUpdateRequestToEntity(UpdateModeRequest $request, ModeEntity $existingEntity): void
    {
        // 只updaterequest中contain的allow修改的field
        $existingEntity->setNameI18n($request->getNameI18n());
        $existingEntity->setPlaceholderI18n($request->getPlaceholderI18n());
        $existingEntity->setIdentifier($request->getIdentifier());
        $existingEntity->setSort($request->getSort());

        if ($request->getIcon() !== null) {
            $existingEntity->setIcon($request->getIcon());
        }

        $iconType = $request->input('icon_type');
        if ($iconType !== null) {
            $existingEntity->setIconType((int) $iconType);
        }

        $iconUrl = $request->input('icon_url');
        if ($iconUrl !== null) {
            $existingEntity->setIconUrl($iconUrl);
        }

        if ($request->getColor() !== null) {
            $existingEntity->setColor($request->getColor());
        }

        if (! is_null($request->getDistributionType())) {
            $existingEntity->setDistributionType($request->getDistributionType());
        }

        if (! is_null($request->getFollowModeId())) {
            $existingEntity->setFollowModeId($request->getFollowModeId());
        }

        if (! is_null($request->getRestrictedModeIdentifiers())) {
            $existingEntity->setRestrictedModeIdentifiers($request->getRestrictedModeIdentifiers());
        }
    }

    /**
     * CreateModeGroupRequest转换为ModeGroupEntity.
     */
    public static function createModeGroupRequestToEntity(CreateModeGroupRequest $request): ModeGroupEntity
    {
        return new ModeGroupEntity($request->all());
    }

    /**
     * UpdateModeGroupRequest转换为ModeGroupEntity.
     */
    public static function updateModeGroupRequestToEntity(UpdateModeGroupRequest $request, string $groupId): ModeGroupEntity
    {
        $entity = new ModeGroupEntity($request->all());
        $entity->setId($groupId);
        return $entity;
    }
}
