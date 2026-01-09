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
     * passIDorModelIDquerymodel
     * based oncanusemodellistconductmatch，meanwhilematchidandmodel_idfield.
     */
    public function getByIdOrModelId(ProviderDataIsolation $dataIsolation, string $id): ?ProviderModelEntity
    {
        // get所havecategorycanusemodel
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // loopjudge id equal $id or者 model_id equal $id
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
            // updatemodel：verifymodelwhether存in（getByIdwillinnot存ino clockthrowexception）
            $this->providerModelRepository->getById($dataIsolation, $providerModelDTO->getId());
        } else {
            // createmodelo clockdefaultenable
            $providerModelDTO->setStatus(Status::Enabled);
        }
        // verify service_provider_config_id whether存in
        if ($providerModelDTO->getServiceProviderConfigId()) {
            $providerConfigEntity = $this->providerConfigRepository->getById($dataIsolation, (int) $providerModelDTO->getServiceProviderConfigId());
            if ($providerConfigEntity === null) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
            }
        }

        // 目frontsavemodelinterfaceonly大modeluse，thereforeforcetypeis llm
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
     * batchquantityaccording toIDgetmodel.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string[] $ids modelIDarray
     * @return ProviderModelEntity[] model实bodyarray，byIDforkey
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
     * batchquantityaccording toModelIDgetmodel.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string[] $modelIds modelidentifierarray
     * @return array<string, ProviderModelEntity[]> model实bodyarray，bymodel_idforkey，valueforto应modellist
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
     * according toqueryitemitemget按modeltypeminutegroupmodelIDlist.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelQuery $query queryitemitem
     * @return array<string, array<string>> 按modeltypeminutegroupmodelIDarray，format: [modelType => [model_id, model_id]]
     */
    public function getModelIdsGroupByType(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query): array
    {
        return $this->providerModelRepository->getModelIdsGroupByType($dataIsolation, $query);
    }

    /**
     * getfinger定modelmost新configurationversionID.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|int most新versionID，ifnot存inthenreturnnull
     */
    public function getLatestConfigVersionId(ProviderDataIsolation $dataIsolation, int $serviceProviderModelId): ?int
    {
        return $this->providerModelConfigVersionRepository->getLatestVersionId($dataIsolation, $serviceProviderModelId);
    }

    /**
     * getfinger定modelmost新configurationversion实body.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderModelId modelID
     * @return null|ProviderModelConfigVersionEntity most新version实body，ifnot存inthenreturnnull
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
        // ifconfigurationforempty，notcreateversionrecord
        if ($modelEntity->getConfig() === null) {
            return;
        }

        // convertforconfigurationversion实bodyandsave（transaction、versionnumber递增、markcurrentversionallin Repository insidecomplete）
        $versionEntity = ProviderModelAssembler::toConfigVersionEntity($modelEntity);
        $this->providerModelConfigVersionRepository->saveVersionWithTransaction($dataIsolation, $versionEntity);
    }
}
