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
     * according toIDget模typeaggregateroot（contain模typedetail、group、modelclose系）.
     */
    public function getModeDetailById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeAggregate
    {
        $mode = $this->modeRepository->findById($dataIsolation, $id);
        if (! $mode) {
            return null;
        }

        // ifis跟随模type，getbe跟随模typegroupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrent模type基本info + be跟随模typegroupconfiguration
                return new ModeAggregate($mode, $followModeAggregate->getGroupAggregates());
            }
        }

        // buildaggregateroot
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
     * according toIDget模type实body（仅get模type基本info）.
     */
    public function getModeById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity
    {
        return $this->modeRepository->findById($dataIsolation, $id);
    }

    /**
     * according toidentifierget模type.
     */
    public function getModeDetailByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeAggregate
    {
        $mode = $this->modeRepository->findByIdentifier($dataIsolation, $identifier);
        if (! $mode) {
            return null;
        }

        // ifis跟随模type，getbe跟随模typegroupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrent模type基本info + be跟随模typegroupconfiguration
                return new ModeAggregate($mode, $followModeAggregate->getGroupAggregates());
            }
        }

        // buildaggregateroot
        return $this->buildModeAggregate($dataIsolation, $mode);
    }

    /**
     * getdefault模type.
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
     * create模type.
     */
    public function createMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        $this->valid($dataIsolation, $modeEntity);
        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * update模type.
     */
    public function updateMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        // ifis跟随模type，validate跟随goal模type存in todo xhy use业务exception
        if ($modeEntity->isInheritedConfiguration() && $modeEntity->hasFollowMode()) {
            $followMode = $this->modeRepository->findById($dataIsolation, $modeEntity->getFollowModeId());
            if (! $followMode) {
                ExceptionBuilder::throw(ModeErrorCode::FOLLOW_MODE_NOT_FOUND);
            }

            // 防止loop跟随
            if ($this->hasCircularFollow($dataIsolation, $modeEntity->getId(), $modeEntity->getFollowModeId())) {
                ExceptionBuilder::throw(ModeErrorCode::CANNOT_FOLLOW_SELF);
            }
        }

        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * update模typestatus
     */
    public function updateModeStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool
    {
        $modeAggregate = $this->getModeDetailById($dataIsolation, $id);
        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }
        $mode = $modeAggregate->getMode();

        // default模typenot能bedisable
        if ($mode->isDefaultMode() && ! $status) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IN_USE_CANNOT_DELETE);
        }

        return $this->modeRepository->updateStatus($dataIsolation, $id, $status);
    }

    /**
     * save模typeconfiguration.
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

        // ifisinheritconfiguration模type
        if ($mode->getDistributionType() === DistributionTypeEnum::INHERITED) {
            return $this->getModeDetailById($dataIsolation, $id);
        }

        // 直接delete该模type所have现haveconfiguration
        $this->relationRepository->deleteByModeId($dataIsolation, $id);

        // delete该模type所have现havegroup
        $this->groupRepository->deleteByModeId($dataIsolation, $id);

        // save模type基本info
        $this->modeRepository->save($dataIsolation, $mode);

        // 批quantitycreategroup副本
        $newGroupEntities = [];
        $maxSort = count($modeAggregate->getGroupAggregates());
        foreach ($modeAggregate->getGroupAggregates() as $index => $groupAggregate) {
            $group = $groupAggregate->getGroup();

            // create新group实body（提frontgenerateID）
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

            // updateaggregatemiddlegroupquote
            $groupAggregate->setGroup($newGroup);
        }

        // 批quantitysavegroup
        if (! empty($newGroupEntities)) {
            $this->groupRepository->batchSave($dataIsolation, $newGroupEntities);
        }

        // 批quantitybuildgroup实bodyandclose系实body
        $relationEntities = [];

        foreach ($modeAggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $relation->setModeId((string) $id);
                $relation->setOrganizationCode($mode->getOrganizationCode());

                // settingfor新creategroupID
                $relation->setGroupId($groupAggregate->getGroup()->getId());

                $relationEntities[] = $relation;
            }
        }

        // 批quantitysaveclose系
        if (! empty($relationEntities)) {
            $this->relationRepository->batchSave($dataIsolation, $relationEntities);
        }

        // returnupdatebackaggregateroot
        return $this->getModeDetailById($dataIsolation, $id);
    }

    /**
     * 批quantitybuild模typeaggregateroot（optimizeversion，避免N+1query）.
     * @param ModeEntity[] $modes
     * @return ModeAggregate[]
     */
    public function batchBuildModeAggregates(ModeDataIsolation $dataIsolation, array $modes): array
    {
        if (empty($modes)) {
            return [];
        }

        // the一步：建立跟随close系mapping followMap[跟随者ID] = be跟随者ID
        $followMap = [];
        $modeIds = [];

        foreach ($modes as $mode) {
            $modeIds[] = $mode->getId();

            // ifis跟随模type，建立mappingclose系
            if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
                $followMap[$mode->getId()] = $mode->getFollowModeId();
                $modeIds[] = $mode->getFollowModeId(); // also要收集be跟随模typeID
            }
        }
        $modeIds = array_unique($modeIds);

        // the二步：批quantityget所havegroupandclose系
        $allGroups = $this->groupRepository->findByModeIds($dataIsolation, $modeIds);
        $allRelations = $this->relationRepository->findByModeIds($dataIsolation, $modeIds);

        // the三步：按模typeIDgroupdata
        $groupsByModeId = [];
        foreach ($allGroups as $group) {
            $groupsByModeId[$group->getModeId()][] = $group;
        }

        $relationsByModeId = [];
        foreach ($allRelations as $relation) {
            $relationsByModeId[$relation->getModeId()][] = $relation;
        }

        // the四步：buildaggregaterootarray
        $aggregates = [];
        foreach ($modes as $mode) {
            $modeId = $mode->getId();

            // findfinal源模typeID（递归find跟随链）
            $ultimateSourceId = $this->findUltimateSourceId($modeId, $followMap);

            $groups = $groupsByModeId[$ultimateSourceId] ?? [];
            $relations = $relationsByModeId[$ultimateSourceId] ?? [];

            // buildgroupaggregaterootarray
            $groupAggregates = [];
            foreach ($groups as $group) {
                // get该groupdown所haveassociateclose系
                $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
                usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

                $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
            }

            $aggregates[] = new ModeAggregate($mode, $groupAggregates);
        }

        return $aggregates;
    }

    /**
     * build模typeaggregateroot.
     */
    private function buildModeAggregate(ModeDataIsolation $dataIsolation, ModeEntity $mode): ModeAggregate
    {
        // getgroupandassociateclose系
        $groups = $this->groupRepository->findByModeId($dataIsolation, $mode->getId());
        $relations = $this->relationRepository->findByModeId($dataIsolation, $mode->getId());

        // buildgroupaggregaterootarray
        $groupAggregates = [];
        foreach ($groups as $group) {
            // typesecuritycheck
            if (! $group instanceof ModeGroupEntity) {
                ExceptionBuilder::throw(ModeErrorCode::VALIDATE_FAILED);
            }

            // get该groupdown所haveassociateclose系
            $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
            usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

            $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
        }

        return new ModeAggregate($mode, $groupAggregates);
    }

    /**
     * checkwhether存inloop跟随.
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
        // validateidentifier唯一property
        if (! $this->modeRepository->isIdentifierUnique($dataIsolation, $modeEntity->getIdentifier())) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IDENTIFIER_ALREADY_EXISTS);
        }
    }

    /**
     * according to跟随close系mapping递归findfinal源模typeID.
     * @param int $modeId current模typeID
     * @param array $followMap 跟随close系mapping [跟随者ID => be跟随者ID]
     * @param array $visited 防止loop跟随
     * @return int final源模typeID
     */
    private function findUltimateSourceId(int $modeId, array $followMap, array $visited = []): int
    {
        // 防止loop跟随
        if (in_array($modeId, $visited)) {
            return $modeId;
        }

        // if该模typenothave跟随close系，instruction它thenisfinal源
        if (! isset($followMap[$modeId])) {
            return $modeId;
        }

        $visited[] = $modeId;

        // 递归find跟随goalfinal源
        return $this->findUltimateSourceId($followMap[$modeId], $followMap, $visited);
    }
}
