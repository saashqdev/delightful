<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Repository\Facade;

use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface ModeRepositoryInterface
{
    /**
     * according toIDgetmode.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity;

    /**
     * according toidentifiergetmode.
     */
    public function findByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeEntity;

    /**
     * getdefaultmode.
     */
    public function findDefaultMode(ModeDataIsolation $dataIsolation): ?ModeEntity;

    /**
     * @return array{total: int, list: ModeEntity[]}
     */
    public function queries(ModeDataIsolation $dataIsolation, ModeQuery $query, Page $page): array;

    /**
     * savemode.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity;

    /**
     * deletemode.
     */
    public function delete(ModeDataIsolation $dataIsolation, string $id): bool;

    /**
     * checkidentifierwhetheruniqueone
     */
    public function isIdentifierUnique(ModeDataIsolation $dataIsolation, string $identifier, ?string $excludeId = null): bool;

    /**
     * get haveenablemode.
     */
    public function findEnabledModes(ModeDataIsolation $dataIsolation): array;

    /**
     * according tofollowmodeIDgetmodecolumntable.
     */
    public function findByFollowModeId(ModeDataIsolation $dataIsolation, string $followModeId): array;

    /**
     * updatemodestatus
     */
    public function updateStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool;
}
