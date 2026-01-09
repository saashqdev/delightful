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
     * according toIDgetmodetypeaggregateroot(containmodetypedetail,group,modelclosesystem).
     */
    public function getModeDetailById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeAggregate
    {
        $mode = $this->modeRepository->findById($dataIsolation, $id);
        if (! $mode) {
            return null;
        }

        // ifisfollowmodetype,getbefollowmodetypegroupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrentmodetypebasicinfo + befollowmodetypegroupconfiguration
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
     * according toIDgetmodetypeactualbody(onlygetmodetypebasicinfo).
     */
    public function getModeById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity
    {
        return $this->modeRepository->findById($dataIsolation, $id);
    }

    /**
     * according toidentifiergetmodetype.
     */
    public function getModeDetailByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeAggregate
    {
        $mode = $this->modeRepository->findByIdentifier($dataIsolation, $identifier);
        if (! $mode) {
            return null;
        }

        // ifisfollowmodetype,getbefollowmodetypegroupconfiguration
        if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
            $followModeAggregate = $this->getModeDetailById($dataIsolation, $mode->getFollowModeId());
            if ($followModeAggregate) {
                // usecurrentmodetypebasicinfo + befollowmodetypegroupconfiguration
                return new ModeAggregate($mode, $followModeAggregate->getGroupAggregates());
            }
        }

        // buildaggregateroot
        return $this->buildModeAggregate($dataIsolation, $mode);
    }

    /**
     * getdefaultmodetype.
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
     * createmodetype.
     */
    public function createMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        $this->valid($dataIsolation, $modeEntity);
        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * updatemodetype.
     */
    public function updateMode(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity
    {
        // ifisfollowmodetype,validatefollowgoalmodetypeexistsin todo xhy usebusinessexception
        if ($modeEntity->isInheritedConfiguration() && $modeEntity->hasFollowMode()) {
            $followMode = $this->modeRepository->findById($dataIsolation, $modeEntity->getFollowModeId());
            if (! $followMode) {
                ExceptionBuilder::throw(ModeErrorCode::FOLLOW_MODE_NOT_FOUND);
            }

            // preventloopfollow
            if ($this->hasCircularFollow($dataIsolation, $modeEntity->getId(), $modeEntity->getFollowModeId())) {
                ExceptionBuilder::throw(ModeErrorCode::CANNOT_FOLLOW_SELF);
            }
        }

        return $this->modeRepository->save($dataIsolation, $modeEntity);
    }

    /**
     * updatemodetypestatus
     */
    public function updateModeStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool
    {
        $modeAggregate = $this->getModeDetailById($dataIsolation, $id);
        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }
        $mode = $modeAggregate->getMode();

        // defaultmodetypenotcanbedisable
        if ($mode->isDefaultMode() && ! $status) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IN_USE_CANNOT_DELETE);
        }

        return $this->modeRepository->updateStatus($dataIsolation, $id, $status);
    }

    /**
     * savemodetypeconfiguration.
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

        // ifisinheritconfigurationmodetype
        if ($mode->getDistributionType() === DistributionTypeEnum::INHERITED) {
            return $this->getModeDetailById($dataIsolation, $id);
        }

        // directlydeletethemodetype haveshowhaveconfiguration
        $this->relationRepository->deleteByModeId($dataIsolation, $id);

        // deletethemodetype haveshowhavegroup
        $this->groupRepository->deleteByModeId($dataIsolation, $id);

        // savemodetypebasicinfo
        $this->modeRepository->save($dataIsolation, $mode);

        // batchquantitycreategroupcopy
        $newGroupEntities = [];
        $maxSort = count($modeAggregate->getGroupAggregates());
        foreach ($modeAggregate->getGroupAggregates() as $index => $groupAggregate) {
            $group = $groupAggregate->getGroup();

            // createnewgroupactualbody(submitfrontgenerateID)
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

        // batchquantitysavegroup
        if (! empty($newGroupEntities)) {
            $this->groupRepository->batchSave($dataIsolation, $newGroupEntities);
        }

        // batchquantitybuildgroupactualbodyandclosesystemactualbody
        $relationEntities = [];

        foreach ($modeAggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $relation->setModeId((string) $id);
                $relation->setOrganizationCode($mode->getOrganizationCode());

                // settingfornewcreategroupID
                $relation->setGroupId($groupAggregate->getGroup()->getId());

                $relationEntities[] = $relation;
            }
        }

        // batchquantitysaveclosesystem
        if (! empty($relationEntities)) {
            $this->relationRepository->batchSave($dataIsolation, $relationEntities);
        }

        // returnupdatebackaggregateroot
        return $this->getModeDetailById($dataIsolation, $id);
    }

    /**
     * batchquantitybuildmodetypeaggregateroot(optimizeversion,avoidN+1query).
     * @param ModeEntity[] $modes
     * @return ModeAggregate[]
     */
    public function batchBuildModeAggregates(ModeDataIsolation $dataIsolation, array $modes): array
    {
        if (empty($modes)) {
            return [];
        }

        // theonestep:establishfollowclosesystemmapping followMap[followpersonID] = befollowpersonID
        $followMap = [];
        $modeIds = [];

        foreach ($modes as $mode) {
            $modeIds[] = $mode->getId();

            // ifisfollowmodetype,establishmappingclosesystem
            if ($mode->isInheritedConfiguration() && $mode->hasFollowMode()) {
                $followMap[$mode->getId()] = $mode->getFollowModeId();
                $modeIds[] = $mode->getFollowModeId(); // alsowantreceivecollectionbefollowmodetypeID
            }
        }
        $modeIds = array_unique($modeIds);

        // thetwostep:batchquantityget havegroupandclosesystem
        $allGroups = $this->groupRepository->findByModeIds($dataIsolation, $modeIds);
        $allRelations = $this->relationRepository->findByModeIds($dataIsolation, $modeIds);

        // thethreestep:by modetypeIDgroupdata
        $groupsByModeId = [];
        foreach ($allGroups as $group) {
            $groupsByModeId[$group->getModeId()][] = $group;
        }

        $relationsByModeId = [];
        foreach ($allRelations as $relation) {
            $relationsByModeId[$relation->getModeId()][] = $relation;
        }

        // thefourstep:buildaggregaterootarray
        $aggregates = [];
        foreach ($modes as $mode) {
            $modeId = $mode->getId();

            // findfinalsource modetypeID(recursionfindfollow chain)
            $ultimateSourceId = $this->findUltimateSourceId($modeId, $followMap);

            $groups = $groupsByModeId[$ultimateSourceId] ?? [];
            $relations = $relationsByModeId[$ultimateSourceId] ?? [];

            // buildgroupaggregaterootarray
            $groupAggregates = [];
            foreach ($groups as $group) {
                // getthegroupdown haveassociateclosesystem
                $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
                usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

                $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
            }

            $aggregates[] = new ModeAggregate($mode, $groupAggregates);
        }

        return $aggregates;
    }

    /**
     * buildmodetypeaggregateroot.
     */
    private function buildModeAggregate(ModeDataIsolation $dataIsolation, ModeEntity $mode): ModeAggregate
    {
        // getgroupandassociateclosesystem
        $groups = $this->groupRepository->findByModeId($dataIsolation, $mode->getId());
        $relations = $this->relationRepository->findByModeId($dataIsolation, $mode->getId());

        // buildgroupaggregaterootarray
        $groupAggregates = [];
        foreach ($groups as $group) {
            // typesecuritycheck
            if (! $group instanceof ModeGroupEntity) {
                ExceptionBuilder::throw(ModeErrorCode::VALIDATE_FAILED);
            }

            // getthegroupdown haveassociateclosesystem
            $groupRelations = array_filter($relations, fn ($relation) => $relation->getGroupId() === $group->getId());
            usort($groupRelations, fn ($a, $b) => $a->getSort() <=> $b->getSort());

            $groupAggregates[] = new ModeGroupAggregate($group, $groupRelations);
        }

        return new ModeAggregate($mode, $groupAggregates);
    }

    /**
     * checkwhetherexistsinloopfollow.
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
        // validateidentifieruniqueoneproperty
        if (! $this->modeRepository->isIdentifierUnique($dataIsolation, $modeEntity->getIdentifier())) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_IDENTIFIER_ALREADY_EXISTS);
        }
    }

    /**
     * according tofollowclosesystemmappingrecursionfindfinalsource modetypeID.
     * @param int $modeId currentmodetypeID
     * @param array $followMap followclosesystemmapping [followpersonID => befollowpersonID]
     * @param array $visited preventloopfollow
     * @return int finalsource modetypeID
     */
    private function findUltimateSourceId(int $modeId, array $followMap, array $visited = []): int
    {
        // preventloopfollow
        if (in_array($modeId, $visited)) {
            return $modeId;
        }

        // ifthemodetypenothavefollowclosesystem,instructionitthenisfinalsource
        if (! isset($followMap[$modeId])) {
            return $modeId;
        }

        $visited[] = $modeId;

        // recursionfindfollowgoalfinalsource
        return $this->findUltimateSourceId($followMap[$modeId], $followMap, $visited);
    }
}
