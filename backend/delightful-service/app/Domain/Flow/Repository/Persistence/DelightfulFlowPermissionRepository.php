<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowPermissionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Permission\ResourceType;
use App\Domain\Flow\Entity\ValueObject\Permission\TargetType;
use App\Domain\Flow\Factory\DelightfulFlowPermissionFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowPermissionRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowPermissionModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowPermissionRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowPermissionRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowPermissionEntity $delightfulFlowPermissionEntity): DelightfulFlowPermissionEntity
    {
        $model = $this->createBuilder($dataIsolation, DelightfulFlowPermissionModel::query())
            ->where('resource_type', $delightfulFlowPermissionEntity->getResourceType()->value)
            ->where('resource_id', $delightfulFlowPermissionEntity->getResourceId())
            ->where('target_type', $delightfulFlowPermissionEntity->getTargetType()->value)
            ->where('target_id', $delightfulFlowPermissionEntity->getTargetId())
            ->first();
        if ($model) {
            $model->fill([
                'operation' => $delightfulFlowPermissionEntity->getOperation()->value,
                'updated_uid' => $delightfulFlowPermissionEntity->getCreator(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $model = new DelightfulFlowPermissionModel();
            $model->fill($this->getAttributes($delightfulFlowPermissionEntity));
        }
        $model->save();

        $delightfulFlowPermissionEntity->setId($model->id);

        return $delightfulFlowPermissionEntity;
    }

    public function getByResourceAndTarget(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, TargetType $targetType, string $targetId): ?DelightfulFlowPermissionEntity
    {
        /** @var null|DelightfulFlowPermissionModel $model */
        $model = $this->createBuilder($dataIsolation, DelightfulFlowPermissionModel::query())
            ->where('resource_type', $resourceType->value)
            ->where('resource_id', $resourceId)
            ->where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->first();
        if ($model === null) {
            return null;
        }
        return DelightfulFlowPermissionFactory::createEntity($model);
    }

    public function getByResource(FlowDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowPermissionModel::query());

        $builder->where('resource_type', $resourceType->value);
        $builder->where('resource_id', $resourceId);

        $data = $this->getByPage($builder, $page);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowPermissionFactory::createEntity($model);
            }
            $data['list'] = $list;
        }
        /* @phpstan-ignore-next-line */
        return $data;
    }

    public function removeByIds(FlowDataIsolation $dataIsolation, array $ids): void
    {
        $this->createBuilder($dataIsolation, DelightfulFlowPermissionModel::query())
            ->whereIn('id', $ids)
            ->delete();
    }
}
