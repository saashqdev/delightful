<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Service;

use App\Domain\Mode\Entity\DistributionTypeEnum;
use App\Domain\Mode\Entity\ModeAggregate;
use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\ModeGroupAggregate;
use App\Domain\Mode\Entity\ModeGroupEntity;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\Domain\Mode\Repository\Facade\ModeGroupRelationRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeGroupRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeRepositoryInterface;
use App\ErrorCode\ModeErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Agent\Assembler\FileAssembler;

class ModeDomainService
{
    public function __construct(
        private ModeRepositoryInterface $modeRepository,
        private ModeGroupRepositoryInterface $groupRepository,
        private ModeGroupRelationRepositoryInterface $relationRepository
    ) {
    }

    /**
     * @return array{total: int, list: ModeEntity[]}
     */
    public function getModes(ModeDataIsolation $dataIsolation, ModeQuery $query, Page $page): array
    {
        return $this->modeRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * according toIDget模式aggregate根（contain模式详情、group、模型关系）.
     */
    public function getModeDetailById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeAggregate
    {
        $mode = $this->modeRepository->findById($dataIsolation, $id);
        if (! $mode) {
            return null;
        }

        // 如果是跟随模式，get被跟随模式的groupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrent模式的基本info + 被跟随模式的groupconfiguration
                return new ModeAggregate($mode, $followModeAggregate->getGroupAggregates());
            }
        }

        // buildaggregate根
        return $this->buildModeAggregate($dataIsolation, $mode);
    }

    public function getOriginMode(ModeDataIsolation $dataIsolation, int|string $id): ?ModeAggregate
    {
        $mode = $this->modeRepository->findById($dataIsolation, $id);
        if (! $mode) {
            return null;
        }
        return $this->buildModeAggregate($dataIsolation, $mode);
    }

    /**
     * according toIDget模式实体（仅get模式基本info）.
     */
    public function getModeById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity
    {
        return $this->modeRepository->findById($dataIsolation, $id);
    }

    /**
     * according to标识符get模式.
     */
    public function getModeDetailByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeAggregate
    {
        $mode = $this->modeRepository->findByIdentifier($dataIsolation, $identifier);
        if (! $mode) {
            return null;
        }

        // 如果是跟随模式，get被跟随模式的groupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrent模式的基本info + 被跟随模式的groupconfiguration
                return new ModeAggregate($mode, $followModeAggregate->getGroupAggregates());
            }
        }

        // buildaggregate根
        return $this->buildModeAggregate($dataIsolation, $mode);
    }

    /**
     * getdefault模式.
     */
    public function getDefaultMode(ModeDataIsolation $dataIsolation): ?ModeAggregate
    {
        $defaultMode = $this->modeRepository->findDefaultMode($dataIsolation);
        if (! $defaultMode) {
            return null;
        }

        return $this->buildModeAggregate($dataIsolation, $defaultMode);
    }

    /**
     * create模式.
     */
    public function createMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        $this->valid($dataIsolation, $modeEntity);
        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * update模式.
     */
    public function updateMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        // 如果是跟随模式，validate跟随的目标模式存在 todo xhy use业务exception
        if ($modeEntity->isInheritedConfiguration() && $modeEntity->hasFollowMode()) {
            $followMode = $this->modeRepository->findById($dataIsolation, $modeEntity->getFollowModeId());
            if (! $followMode) {
                ExceptionBuilder::throw(ModeErrorCode::FOLLOW_MODE_NOT_FOUND);
            }

            // 防止循环跟随
            if ($this->hasCircularFollow($dataIsolation, $modeEntity->getId(), $modeEntity->getFollowModeId())) {
                ExceptionBuilder::throw(ModeErrorCode::CANNOT_FOLLOW_SELF);
            }
        }

        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * update模式status
     */
    public function updateModeStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool
    {
        $modeAggregate = $this->getModeDetailById($dataIsolation, $id);
        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }
        $mode = $modeAggregate->getMode();

        // default模式不能被禁用
        if ($mode->isDefaultMode() && ! $status) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IN_USE_CANNOT_DELETE);
        }

        return $this->modeRepository->updateStatus($dataIsolation, $id, $status);
    }

    /**
     * save模式configuration.
     */
    public function saveModeConfig(ModeDataIsolation $dataIsolation, ModeAggregate $modeAggregate): ModeAggregate
    {
        $mode = $modeAggregate->getMode();

        $id = $mode->getId();
        $modeEntity = $this->getModeById($dataIsolation, $id);
        $followModeId = $mode->getFollowModeId();
        $modeEntity->setFollowModeId($followModeId);
        $modeEntity->setDistributionType($mode->getDistributionType());

        $this->updateMode($dataIsolation, $modeEntity);

        // 如果是inheritconfiguration模式
        if ($mode->getDistributionType() === DistributionTypeEnum::INHERITED) {
            return $this->getModeDetailById($dataIsolation, $id);
        }

        // 直接delete该模式的所有现有configuration
        $this->relationRepository->deleteByModeId($dataIsolation, $id);

        // delete该模式的所有现有group
        $this->groupRepository->deleteByModeId($dataIsolation, $id);

        // save模式基本info
        $this->modeRepository->save($dataIsolation, $mode);

        // 批量creategroup副本
        $newGroupEntities = [];
        $maxSort = count($modeAggregate->getGroupAggregates());
        foreach ($modeAggregate->getGroupAggregates() as $index => $groupAggregate) {
            $group = $groupAggregate->getGroup();

            // create新group实体（提前generateID）
            $newGroup = new ModeGroupEntity();
            $newGroup->setId(IdGenerator::getSnowId());
            $newGroup->setModeId((int) $id);
            $newGroup->setNameI18n($group->getNameI18n());
            $newGroup->setIcon(FileAssembler::formatPath($group->getIcon()));
            $newGroup->setDescription($group->getDescription());
            $newGroup->setSort($maxSort - $index);
            $newGroup->setStatus($group->getStatus());
            $newGroup->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
            $newGroup->setCreatorId($dataIsolation->getCurrentUserId());

            $newGroupEntities[] = $newGroup;

            // updateaggregate中的groupquote
            $groupAggregate->setGroup($newGroup);
        }

        // 批量savegroup
        if (! empty($newGroupEntities)) {
            $this->groupRepository->batchSave($dataIsolation, $newGroupEntities);
        }

        // 批量buildgroup实体和关系实体
        $relationEntities = [];

        foreach ($modeAggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $relation->setModeId((string) $id);
                $relation->setOrganizationCode($mode->getOrganizationCode());

                // setting为新create的groupID
                $relation->setGroupId($groupAggregate->getGroup()->getId());

                $relationEntities[] = $relation;
            }
        }

        // 批量save关系
        if (! empty($relationEntities)) {
            $this->relationRepository->batchSave($dataIsolation, $relationEntities);
        }

        // returnupdate后的aggregate根
        return $this->getModeDetailById($dataIsolation, $id);
    }

    /**
     * 批量build模式aggregate根（optimizeversion，避免N+1query）.
     * @param ModeEntity[] $modes
     * @return ModeAggregate[]
     */
    public function batchBuildModeAggregates(ModeDataIsolation $dataIsolation, array $modes): array
    {
        if (empty($modes)) {
            return [];
        }

        // 第一步：建立跟随关系映射 followMap[跟随者ID] = 被跟随者ID
        $followMap = [];
        $modeIds = [];

        foreach ($modes as $mode) {
            $modeIds[] = $mode->getId();

            // 如果是跟随模式，建立映射关系
            if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
                $followMap[$mode->getId()] = $mode->getFollowModeId();
                $modeIds[] = $mode->getFollowModeId(); // 也要收集被跟随的模式ID
            }
        }
        $modeIds = array_unique($modeIds);

        // 第二步：批量get所有group和关系
        $allGroups = $this->groupRepository->findByModeIds($dataIsolation, $modeIds);
        $allRelations = $this->relationRepository->findByModeIds($dataIsolation, $modeIds);

        // 第三步：按模式IDgroup数据
        $groupsByModeId = [];
        foreach ($allGroups as $group) {
            $groupsByModeId[$group->getModeId()][] = $group;
        }

        $relationsByModeId = [];
        foreach ($allRelations as $relation) {
            $relationsByModeId[$relation->getModeId()][] = $relation;
        }

        // 第四步：buildaggregate根array
        $aggregates = [];
        foreach ($modes as $mode) {
            $modeId = $mode->getId();

            // 查找final源模式ID（递归查找跟随链）
            $ultimateSourceId = $this->findUltimateSourceId($modeId, $followMap);

            $groups = $groupsByModeId[$ultimateSourceId] ?? [];
            $relations = $relationsByModeId[$ultimateSourceId] ?? [];

            // buildgroupaggregate根array
            $groupAggregates = [];
            foreach ($groups as $group) {
                // get该group下的所有关联关系
                $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
                usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

                $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
            }

            $aggregates[] = new ModeAggregate($mode, $groupAggregates);
        }

        return $aggregates;
    }

    /**
     * build模式aggregate根.
     */
    private function buildModeAggregate(ModeDataIsolation $dataIsolation, ModeEntity $mode): ModeAggregate
    {
        // getgroup和关联关系
        $groups = $this->groupRepository->findByModeId($dataIsolation, $mode->getId());
        $relations = $this->relationRepository->findByModeId($dataIsolation, $mode->getId());

        // buildgroupaggregate根array
        $groupAggregates = [];
        foreach ($groups as $group) {
            // type安全check
            if (! $group instanceof ModeGroupEntity) {
                ExceptionBuilder::throw(ModeErrorCode::VALIDATE_FAILED);
            }

            // get该group下的所有关联关系
            $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
            usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

            $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
        }

        return new ModeAggregate($mode, $groupAggregates);
    }

    /**
     * check是否存在循环跟随.
     */
    private function hasCircularFollow(ModeDataIsolation $dataIsolation, int|string $modeId, int|string $followModeId, array $visited = []): bool
    {
        if (in_array($followModeId, $visited)) {
            return true;
        }

        $visited[] = $followModeId;

        $followMode = $this->modeRepository->findById($dataIsolation, $followModeId);
        if (! $followMode || ! $followMode->isInheritedConfiguration() || ! $followMode->hasFollowMode()) {
            return false;
        }

        if ($followMode->getFollowModeId() === (int) $modeId) {
            return true;
        }

        return $this->hasCircularFollow($dataIsolation, $modeId, $followMode->getFollowModeId(), $visited);
    }

    private function valid(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity)
    {
        // validate标识符唯一性
        if (! $this->modeRepository->isIdentifierUnique($dataIsolation, $modeEntity->getIdentifier())) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IDENTIFIER_ALREADY_EXISTS);
        }
    }

    /**
     * according to跟随关系映射递归查找final源模式ID.
     * @param int $modeId current模式ID
     * @param array $followMap 跟随关系映射 [跟随者ID => 被跟随者ID]
     * @param array $visited 防止循环跟随
     * @return int final源模式ID
     */
    private function findUltimateSourceId(int $modeId, array $followMap, array $visited = []): int
    {
        // 防止循环跟随
        if (in_array($modeId, $visited)) {
            return $modeId;
        }

        // 如果该模式没有跟随关系，说明它就是final源
        if (! isset($followMap[$modeId])) {
            return $modeId;
        }

        $visited[] = $modeId;

        // 递归查找跟随目标的final源
        return $this->findUltimateSourceId($followMap[$modeId], $followMap, $visited);
    }
}
