<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\ProviderModelConfigVersionEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Repository\Facade\ProviderModelConfigVersionRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelConfigVersionModel;
use Hyperf\DbConnection\Db;

class ProviderModelConfigVersionRepository extends AbstractProviderModelRepository implements ProviderModelConfigVersionRepositoryInterface
{
    // configurationversiontablenotneedorganization隔离（pass service_provider_model_id associate已经隔离）
    protected bool $filterOrganizationCode = false;

    /**
     * savemodelconfigurationversion（intransaction中completeversion号递增、mark旧version、create新version）.
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void
    {
        Db::transaction(function () use ($dataIsolation, $entity) {
            $serviceProviderModelId = $entity->getServiceProviderModelId();

            // 1. getmost新version号并计算新version号（use FOR UPDATE 行lock防止并发issue）
            $builder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
            $latestVersion = $builder
                ->where('service_provider_model_id', $serviceProviderModelId)
                ->lockForUpdate()  // 悲观lock，防止并发
                ->max('version');

            $newVersion = $latestVersion ? (int) $latestVersion + 1 : 1;

            // 2. 将该model的所have旧versionmark为noncurrentversion
            $updateBuilder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
            $updateBuilder
                ->where('service_provider_model_id', $serviceProviderModelId)
                ->where('is_current_version', true)
                ->update(['is_current_version' => false]);

            // 3. setversion号并create新versionrecord
            $entity->setVersion($newVersion);
            $entity->setIsCurrentVersion(true);

            // convert为array并移except null 的 created_at，让 Model 自动processtime戳
            $data = $entity->toArray();

            ProviderModelConfigVersionModel::query()->create($data);
        });
    }

    /**
     * get指定model的most新versionID.
     */
    public function getLatestVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
        return $builder
            ->where('service_provider_model_id', $serviceProviderModelId)
            ->where('is_current_version', true)
            ->value('id');
    }

    /**
     * get指定model的most新configurationversion实体.
     */
    public function getLatestVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
        $model = $builder
            ->where('service_provider_model_id', $serviceProviderModelId)
            ->where('is_current_version', true)
            ->first();

        if ($model === null) {
            return null;
        }

        return new ProviderModelConfigVersionEntity($model->toArray());
    }
}
