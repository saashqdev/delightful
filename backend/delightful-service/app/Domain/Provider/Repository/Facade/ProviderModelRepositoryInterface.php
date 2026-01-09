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
     * pass service_provider_config_id getmodellist.
     * @return ProviderModelEntity[]
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId, ProviderEntity $providerEntity): array;

    /**
     * getorganizationcanusemodellist(containorganizationfrom己modelandDelightfulmodel).
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param null|Category $category modelcategory,foremptyo clockreturn所havecategorymodel
     * @return ProviderModelEntity[] 按sort降序sortmodellist,containorganizationmodelandDelightfulmodel(notgo重)
     */
    public function getModelsForOrganization(ProviderDataIsolation $dataIsolation, ?Category $category = null, Status $status = Status::Enabled): array;

    /**
     * batchquantityaccording toIDgetmodel.
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param string[] $ids modelIDarray
     * @return ProviderModelEntity[] model实bodyarray,byIDforkey
     */
    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array;

    public function getModelByIdWithoutOrgFilter(string $id): ?ProviderModelEntity;

    /**
     * batchquantityaccording toModelIDgetmodel.
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param string[] $modelIds modelidentifierarray
     * @return array<string, ProviderModelEntity[]> model实bodyarray,bymodel_idforkey,valueforto应modellist
     */
    public function getByModelIds(ProviderDataIsolation $dataIsolation, array $modelIds): array;

    /**
     * @return array{total: int, list: ProviderModelEntity[]}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query, Page $page): array;

    /**
     * according toqueryitemitemget按modeltypeminutegroupmodelIDlist.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param ProviderModelQuery $query queryitemitem
     * @return array<string, array<string>> 按modeltypeminutegroupmodelIDarray,format: [modelType => [model_id, model_id]]
     */
    public function getModelIdsGroupByType(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query): array;
}
