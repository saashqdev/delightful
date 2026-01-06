<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowPermissionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Permission\ResourceType;
use App\Domain\Flow\Entity\ValueObject\Permission\TargetType;
use App\Domain\Flow\Repository\Facade\DelightfulFlowPermissionRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowPermissionDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowPermissionRepositoryInterface $magicFlowPermissionRepository,
    ) {
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowPermissionEntity $permissionEntity): DelightfulFlowPermissionEntity
    {
        $permissionEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $permissionEntity->setCreator($dataIsolation->getCurrentUserId());
        $permissionEntity->prepareForSave();
        return $this->magicFlowPermissionRepository->save($dataIsolation, $permissionEntity);
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowPermissionEntity>}
     */
    public function getByResource(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, Page $page): array
    {
        /* @phpstan-ignore-next-line */
        return $this->magicFlowPermissionRepository->getByResource($dataIsolation, $resourceType, $resourceId, $page);
    }

    public function removeByIds(FlowDataIsolation $dataIsolation, array $ids): void
    {
        $this->magicFlowPermissionRepository->removeByIds($dataIsolation, $ids);
    }

    public function getByResourceAndTarget(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, TargetType $targetType, string $targetId): ?DelightfulFlowPermissionEntity
    {
        return $this->magicFlowPermissionRepository->getByResourceAndTarget($dataIsolation, $resourceType, $resourceId, $targetType, $targetId);
    }
}
