<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;

interface ProviderModelRepositoryInterface
{
    public function getAvailableByModelIdOrId(ProviderDataIsolation $dataIsolation, string $modelId, bool $checkStatus = true): ?ProviderModelEntity;

    public function getById(ProviderDataIsolation $dataIsolation, string $id): ProviderModelEntity;

    public function getByModelId(ProviderDataIsolation $dataIsolation, string $modelId): ?ProviderModelEntity;

    /**
     * @return ProviderModelEntity[]
     */
    public function getByProviderConfigId(ProviderDataIsolation $dataIsolation, string $providerConfigId): array;

    public function deleteByProviderId(ProviderDataIsolation $dataIsolation, string $providerId): void;

    public function deleteById(ProviderDataIsolation $dataIsolation, string $id): void;

    public function saveModel(ProviderDataIsolation $dataIsolation, SaveProviderModelDTO $dto): ProviderModelEntity;

    public function updateStatus(ProviderDataIsolation $dataIsolation, string $id, Status $status): void;

    public function deleteByModelParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): void;

    public function deleteByModelParentIds(ProviderDataIsolation $dataIsolation, array $modelParentIds): void;

    public function create(ProviderDataIsolation $dataIsolation, ProviderModelEntity $modelEntity): ProviderModelEntity;

    /**
     * 通过 service_provider_config_id getmodellist.
     * @return ProviderModelEntity[]
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId, ProviderEntity $providerEntity): array;

    /**
     * getorganization可用modellist（包含organization自己的model和Delightfulmodel）.
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param null|Category $category model分类，为空时return所有分类model
     * @return ProviderModelEntity[] 按sort降序sort的modellist，包含organizationmodel和Delightfulmodel（不去重）
     */
    public function getModelsForOrganization(ProviderDataIsolation $dataIsolation, ?Category $category = null, Status $status = Status::Enabled): array;

    /**
     * 批量according toIDgetmodel.
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param string[] $ids modelIDarray
     * @return ProviderModelEntity[] model实体array，以ID为键
     */
    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array;

    public function getModelByIdWithoutOrgFilter(string $id): ?ProviderModelEntity;

    /**
     * 批量according toModelIDgetmodel.
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param string[] $modelIds model标识array
     * @return array<string, ProviderModelEntity[]> model实体array，以model_id为键，value为对应的modellist
     */
    public function getByModelIds(ProviderDataIsolation $dataIsolation, array $modelIds): array;

    /**
     * @return array{total: int, list: ProviderModelEntity[]}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query, Page $page): array;

    /**
     * according toquery条件get按modeltype分组的modelIDlist.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离object
     * @param ProviderModelQuery $query query条件
     * @return array<string, array<string>> 按modeltype分组的modelIDarray，格式: [modelType => [model_id, model_id]]
     */
    public function getModelIdsGroupByType(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query): array;
}
