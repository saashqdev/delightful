<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowPermissionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Permission\ResourceType;
use App\Domain\Flow\Entity\ValueObject\Permission\TargetType;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowPermissionRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowPermissionEntity $delightfulFlowPermissionEntity): DelightfulFlowPermissionEntity;

    public function getByResourceAndTarget(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, TargetType $targetType, string $targetId): ?DelightfulFlowPermissionEntity;

    /**
     * @return DelightfulFlowPermissionEntity[]
     */
    public function getByResource(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, Page $page): array;

    public function removeByIds(FlowDataIsolation $dataIsolation, array $ids): void;
}
