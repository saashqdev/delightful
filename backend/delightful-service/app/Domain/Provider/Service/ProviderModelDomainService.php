<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\Entity\ProviderModelConfigVersionEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ModelType;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\ProviderConfigRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelConfigVersionRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelRepositoryInterface;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Provider\Assembler\ProviderModelAssembler;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;

readonly class ProviderModelDomainService
{
    public function __construct(
        private ProviderModelRepositoryInterface $providerModelRepository,
        private ProviderConfigRepositoryInterface $providerConfigRepository,
        private ProviderModelConfigVersionRepositoryInterface $providerModelConfigVersionRepository,
    ) {
    }

    public function getAvailableByModelIdOrId(ProviderDataIsolation $dataIsolation, string $modelId, bool $checkStatus = true): ?ProviderModelEntity
    {
        return $this->providerModelRepository->getAvailableByModelIdOrId($dataIsolation, $modelId, $checkStatus);
    }

    public function getById(ProviderDataIsolation $dataIsolation, string $id): ProviderModelEntity
    {
        return $this->providerModelRepository->getById($dataIsolation, $id);
    }

    public function getByModelId(ProviderDataIsolation $dataIsolation, string $modelId): ?ProviderModelEntity
    {
        return $this->providerModelRepository->getByModelId($dataIsolation, $modelId);
    }

    /**
     * passID或ModelIDquerymodel
     * based on可用modellist进行匹配，同时匹配id和model_idfield.
     */
    public function getByIdOrModelId(ProviderDataIsolation $dataIsolation, string $id): ?ProviderModelEntity
    {
        // get所有category的可用model
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // 循环判断 id equal $id 或者 model_id equal $id
        foreach ($allModels as $model) {
            if ((string) $model->getId() === $id || $model->getModelId() === $id) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @return ProviderModelEntity[]
     */
    public function getByProviderConfigId(ProviderDataIsolation $dataIsolation, string $providerConfigId): array
    {
        return $this->providerModelRepository->getByProviderConfigId($dataIsolation, $providerConfigId);
    }

    public function deleteByProviderId(ProviderDataIsolation $dataIsolation, string $providerId): void
    {
        $this->providerModelRepository->deleteByProviderId($dataIsolation, $providerId);
    }

    public function deleteById(ProviderDataIsolation $dataIsolation, string $id): void
    {
        $this->providerModelRepository->deleteById($dataIsolation, $id);
    }

    public function saveModel(ProviderDataIsolation $dataIsolation, SaveProviderModelDTO $providerModelDTO): SaveProviderModelDTO
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        $providerModelDTO->setOrganizationCode($organizationCode);
        if ($providerModelDTO->getModelType() === ModelType::EMBEDDING) {
            $providerModelDTO->getConfig()?->setSupportEmbedding(true);
        }

        if ($providerModelDTO->getId()) {
            // updatemodel：verifymodel是否存在（getByIdwill在不存在时throwexception）
            $this->providerModelRepository->getById($dataIsolation, $providerModelDTO->getId());
        } else {
            // createmodel时defaultenable
            $providerModelDTO->setStatus(Status::Enabled);
        }
        // verify service_provider_config_id 是否存在
        if ($providerModelDTO->getServiceProviderConfigId()) {
            $providerConfigEntity = $this->providerConfigRepository->getById($dataIsolation, (int) $providerModelDTO->getServiceProviderConfigId());
            if ($providerConfigEntity === null) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
            }
        }

        // 目前savemodel的interface只有大modeluse，因此forcetype是 llm
        $providerModelDTO->setCategory(Category::LLM);
        $modelEntity = $this->providerModelRepository->saveModel($dataIsolation, $providerModelDTO);

        // createconfigurationversionrecord
        $this->saveConfigVersion($dataIsolation, $modelEntity);

        return new SaveProviderModelDTO($modelEntity->toArray());
    }

    public function updateStatus(ProviderDataIsolation $dataIsolation, string $id, Status $status): void
    {
        $this->providerModelRepository->updateStatus($dataIsolation, $id, $status);
    }

    public function deleteByModelParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): void
    {
        $this->providerModelRepository->deleteByModelParentId($dataIsolation, $modelParentId);
    }

    public function deleteByModelParentIds(ProviderDataIsolation $dataIsolation, array $modelParentIds): void
    {
        $this->providerModelRepository->deleteByModelParentIds($dataIsolation, $modelParentIds);
    }

    /**
     * 批量according toIDgetmodel.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string[] $ids modelIDarray
     * @return ProviderModelEntity[] model实体array，以ID为键
     */
    public function getModelsByIds(ProviderDataIsolation $dataIsolation, array $ids): array
    {
        return $this->providerModelRepository->getByIds($dataIsolation, $ids);
    }

    public function getModelById(string $id): ?ProviderModelEntity
    {
        return $this->providerModelRepository->getModelByIdWithoutOrgFilter($id);
    }

    /**
     * 批量according toModelIDgetmodel.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string[] $modelIds model标识array
     * @return array<string, ProviderModelEntity[]> model实体array，以model_id为键，value为对应的modellist
     */
    public function getModelsByModelIds(ProviderDataIsolation $dataIsolation, array $modelIds): array
    {
        return $this->providerModelRepository->getByModelIds($dataIsolation, $modelIds);
    }

    /**
     * @return array{total: int, list: ProviderModelEntity[]}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query, Page $page): array
    {
        return $this->providerModelRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * according toquery条件get按modeltype分group的modelIDlist.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelQuery $query query条件
     * @return array<string, array<string>> 按modeltype分group的modelIDarray，format: [modelType => [model_id, model_id]]
     */
    public function getModelIdsGroupByType(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query): array
    {
        return $this->providerModelRepository->getModelIdsGroupByType($dataIsolation, $query);
    }

    /**
     * get指定model的最新configurationversionID.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int 最新version的ID，如果不存在则returnnull
     */
    public function getLatestConfigVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int
    {
        return $this->providerModelConfigVersionRepository->getLatestVersionId($dataIsolation, $serviceProviderModelId);
    }

    /**
     * get指定model的最新configurationversion实体.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity 最新version的实体，如果不存在则returnnull
     */
    public function getLatestConfigVersionEntity(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?ProviderModelConfigVersionEntity
    {
        return $this->providerModelConfigVersionRepository->getLatestVersionEntity($dataIsolation, $serviceProviderModelId);
    }

    /**
     * savemodelconfigurationversion.
     */
    private function saveConfigVersion(ProviderDataIsolation $dataIsolation, ProviderModelEntity $modelEntity): void
    {
        // 如果configuration为空，不createversionrecord
        if ($modelEntity->getConfig() === null) {
            return;
        }

        // convert为configurationversion实体并save（transaction、version号递增、markcurrentversion都在 Repository 内complete）
        $versionEntity = ProviderModelAssembler::toConfigVersionEntity($modelEntity);
        $this->providerModelConfigVersionRepository->saveVersionWithTransaction($dataIsolation, $versionEntity);
    }
}
