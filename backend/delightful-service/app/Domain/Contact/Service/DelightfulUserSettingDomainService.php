<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\Query\DelightfulUserSettingQuery;
use App\Domain\Contact\Repository\Facade\DelightfulUserSettingRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;

readonly class DelightfulUserSettingDomainService
{
    public function __construct(
        private DelightfulUserSettingRepositoryInterface $delightfulUserSettingRepository
    ) {
    }

    public function get(DataIsolation $dataIsolation, string $key): ?DelightfulUserSettingEntity
    {
        return $this->delightfulUserSettingRepository->get($dataIsolation, $key);
    }

    /**
     * get全局configuration.
     */
    public function getGlobal(string $key): ?DelightfulUserSettingEntity
    {
        return $this->delightfulUserSettingRepository->getGlobal($key);
    }

    /**
     * save全局configuration.
     */
    public function saveGlobal(DelightfulUserSettingEntity $savingEntity): DelightfulUserSettingEntity
    {
        return $this->delightfulUserSettingRepository->saveGlobal($savingEntity);
    }

    /**
     * @return array{total: int, list: array<DelightfulUserSettingEntity>}
     */
    public function queries(DataIsolation $dataIsolation, DelightfulUserSettingQuery $query, Page $page): array
    {
        return $this->delightfulUserSettingRepository->queries($dataIsolation, $query, $page);
    }

    public function save(DataIsolation $dataIsolation, DelightfulUserSettingEntity $savingEntity): DelightfulUserSettingEntity
    {
        $savingEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $savingEntity->setDelightfulId($dataIsolation->getCurrentDelightfulId());
        $savingEntity->setUserId($dataIsolation->getCurrentUserId());

        $existingEntity = $this->delightfulUserSettingRepository->get($dataIsolation, $savingEntity->getKey());
        if ($existingEntity) {
            $savingEntity->prepareForModification($existingEntity);
            $entity = $savingEntity;
        } else {
            $entity = clone $savingEntity;
            $entity->prepareForCreation();
        }

        return $this->delightfulUserSettingRepository->save($dataIsolation, $entity);
    }

    /**
     * pass delightfulId saveusersetting（跨organization）.
     */
    public function saveByDelightfulId(string $delightfulId, DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity
    {
        // get现有record以保持实体完整性
        $existingEntity = $this->delightfulUserSettingRepository->getByDelightfulId($delightfulId, $delightfulUserSettingEntity->getKey());

        if ($existingEntity) {
            $delightfulUserSettingEntity->prepareForModification($existingEntity);
        } else {
            $delightfulUserSettingEntity->prepareForCreation();
        }

        return $this->delightfulUserSettingRepository->saveByDelightfulId($delightfulId, $delightfulUserSettingEntity);
    }

    /**
     * pass delightfulId getusersetting（跨organization）.
     */
    public function getByDelightfulId(string $delightfulId, string $key): ?DelightfulUserSettingEntity
    {
        return $this->delightfulUserSettingRepository->getByDelightfulId($delightfulId, $key);
    }
}
