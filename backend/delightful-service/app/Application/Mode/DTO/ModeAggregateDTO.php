<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\DTO;

use App\Infrastructure\Core\AbstractDTO;

class ModeAggregateDTO extends AbstractDTO
{
    protected ModeDTO $mode;

    /**
     * @var ModeGroupAggregateDTO[] minutegroupaggregaterootarray
     */
    protected array $groups = [];

    public function getMode(): ModeDTO
    {
        return $this->mode;
    }

    public function setMode(array|ModeDTO $mode): void
    {
        $this->mode = $mode instanceof ModeDTO ? $mode : new ModeDTO($mode);
    }

    /**
     * @return ModeGroupAggregateDTO[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $groupData = [];
        foreach ($groups as $group) {
            $groupData[] = $group instanceof ModeGroupAggregateDTO ? $group : new ModeGroupAggregateDTO($group);
        }

        $this->groups = $groupData;
    }

    /**
     * addminutegroupaggregateroot.
     */
    public function addGroupAggregate(ModeGroupAggregateDTO $groupAggregate): void
    {
        $this->groups[] = $groupAggregate;
    }

    /**
     * according tominutegroupIDgetminutegroupaggregateroot.
     */
    public function getGroupAggregateByGroupId(string $groupId): ?ModeGroupAggregateDTO
    {
        foreach ($this->groups as $groupAggregate) {
            if ($groupAggregate->getGroup()->getId() === $groupId) {
                return $groupAggregate;
            }
        }
        return null;
    }

    /**
     * 移exceptminutegroupaggregateroot.
     */
    public function removeGroupAggregateByGroupId(string $groupId): void
    {
        $this->groups = array_filter(
            $this->groups,
            fn ($groupAggregate) => $groupAggregate->getGroup()->getId() !== $groupId
        );
        $this->groups = array_values($this->groups); // 重新index
    }

    /**
     * get所havemodelID.
     *
     * @return string[]
     */
    public function getAllModelIds(): array
    {
        $allModelIds = [];
        foreach ($this->groups as $groupAggregate) {
            $allModelIds = array_merge($allModelIds, $groupAggregate->getModelIds());
        }
        return array_unique($allModelIds);
    }

    /**
     * getminutegroupquantity.
     */
    public function getGroupCount(): int
    {
        return count($this->groups);
    }

    /**
     * get总modelquantity.
     */
    public function getTotalModelCount(): int
    {
        $count = 0;
        foreach ($this->groups as $groupAggregate) {
            $count += $groupAggregate->getModelCount();
        }
        return $count;
    }
}
