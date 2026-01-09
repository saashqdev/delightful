<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Service;

use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeGroupEntity;
use App\Domain\Mode\Repository\Facade\ModeGroupRelationRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeGroupRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeRepositoryInterface;
use App\ErrorCode\ModeErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

class ModeGroupDomainService
{
    public function __construct(
        private ModeGroupRepositoryInterface $groupRepository,
        private ModeGroupRelationRepositoryInterface $relationRepository,
        private ModeRepositoryInterface $modeRepository
    ) {
    }

    /**
     * according toIDgetminutegroup.
     */
    public function getGroupById(ModeDataIsolation $dataIsolation, string $id): ?ModeGroupEntity
    {
        return $this->groupRepository->findById($dataIsolation, $id);
    }

    /**
     * createminutegroup.
     */
    public function createGroup(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity
    {
        $this->validateModeExists($dataIsolation, $groupEntity->getModeId());

        return $this->groupRepository->save($dataIsolation, $groupEntity);
    }

    /**
     * updateminutegroup.
     */
    public function updateGroup(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity
    {
        $this->validateModeExists($dataIsolation, $groupEntity->getModeId());

        return $this->groupRepository->update($dataIsolation, $groupEntity);
    }

    /**
     * according tomodeIDgetminutegroupcolumntable.
     */
    public function getGroupsByModeId(ModeDataIsolation $dataIsolation, string $modeId): array
    {
        return $this->groupRepository->findByModeId($dataIsolation, $modeId);
    }

    /**
     * deleteminutegroup.
     */
    public function deleteGroup(ModeDataIsolation $dataIsolation, string $groupId): bool
    {
        $group = $this->groupRepository->findById($dataIsolation, $groupId);
        if (! $group) {
            ExceptionBuilder::throw(ModeErrorCode::GROUP_NOT_FOUND);
        }

        // deleteminutegroupdown havemodelassociate
        $this->relationRepository->deleteByGroupId($dataIsolation, $groupId);

        // deleteminutegroup
        return $this->groupRepository->delete($dataIsolation, $groupId);
    }

    /**
     * getminutegroupdownmodelassociate.
     */
    public function getGroupModels(ModeDataIsolation $dataIsolation, string $groupId): array
    {
        return $this->relationRepository->findByGroupId($dataIsolation, $groupId);
    }

    /**
     * verifymodewhetherexistsin.
     */
    private function validateModeExists(ModeDataIsolation $dataIsolation, int $modeId): void
    {
        $mode = $this->modeRepository->findById($dataIsolation, $modeId);
        if (! $mode) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }
    }
}
