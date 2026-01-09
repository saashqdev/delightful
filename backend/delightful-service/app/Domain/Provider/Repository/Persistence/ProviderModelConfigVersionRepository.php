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
     * savemodelconfigurationversion（intransactionmiddlecompleteversionnumber递增、mark旧version、create新version）.
     */
    public function saveVersionWithTransaction(ProviderDataIsolation $dataIsolation, ProviderModelConfigVersionEntity $entity): void
    {
        Db::transaction(function () use ($dataIsolation, $entity) {
            $serviceProviderModelId = $entity->getServiceProviderModelId();

            // 1. getmost新versionnumberandcalculate新versionnumber（use FOR UPDATE linelock防止andhairissue）
            $builder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
            $latestVersion = $builder
                ->where('service_provider_model_id', $serviceProviderModelId)
                ->lockForUpdate()  // 悲观lock，防止andhair
                ->max('version');

            $newVersion = $latestVersion ? (int) $latestVersion + 1 : 1;

            // 2. will该model所have旧versionmarkfornoncurrentversion
            $updateBuilder = $this->createBuilder($dataIsolation, ProviderModelConfigVersionModel::query());
            $updateBuilder
                ->where('service_provider_model_id', $serviceProviderModelId)
                ->where('is_current_version', true)
                ->update(['is_current_version' => false]);

            // 3. setversionnumberandcreate新versionrecord
            $entity->setVersion($newVersion);
            $entity->setIsCurrentVersion(true);

            // convertforarrayand移except null  created_at，let Model from动processtime戳
            $data = $entity->toArray();

            ProviderModelConfigVersionModel::query()->create($data);
        });
    }

    /**
     * getfinger定modelmost新versionID.
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
     * getfinger定modelmost新configurationversion实body.
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
