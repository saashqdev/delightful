<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;
use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\DTO\ProviderConfigModelsDTO;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\ProviderType;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Persistence\ProviderConfigRepository;
use App\Domain\Provider\Repository\Persistence\ProviderModelRepository;
use App\Domain\Provider\Repository\Persistence\ProviderOriginalModelRepository;
use App\Domain\Provider\Repository\Persistence\ProviderRepository;
use App\Domain\Provider\Service\ConnectivityTest\ConnectResponse;
use App\Domain\Provider\Service\ConnectivityTest\ServiceProviderFactory;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Locker\RedisLocker;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderAssembler;
use Exception;
use Hyperf\Contract\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class AdminProviderDomainService extends AbstractProviderDomainService
{
    public function __construct(
        protected ProviderRepository $serviceProviderRepository,
        protected ProviderModelRepository $providerModelRepository,
        protected ProviderConfigRepository $providerConfigRepository,
        protected ProviderOriginalModelRepository $serviceProviderOriginalModelsRepository,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected RedisLocker $redisLocker,
    ) {
    }

    /**
     * getservicequotientconfigurationinfo.
     */
    public function getServiceProviderConfigDetail(string $serviceProviderConfigId, string $organizationCode, bool $decryptConfig = false): ProviderConfigDTO
    {
        // 1. getservicequotientconfiguration实body
        $providerConfigEntity = $this->providerConfigRepository->getProviderConfigEntityById($serviceProviderConfigId, $organizationCode);

        if ($providerConfigEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 2. getservicequotientinfo
        $providerEntity = $this->serviceProviderRepository->getById($providerConfigEntity->getServiceProviderId());

        if ($providerEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 3. group装 ProviderConfigDTO
        $configData = $providerConfigEntity->toArray();
        $providerData = $providerEntity->toArray();
        // mergeconfigurationandservicequotientdata
        $mergedData = array_merge($configData, [
            'name' => $providerData['name'],
            'description' => $providerData['description'],
            'icon' => $providerData['icon'],
            'provider_type' => $providerData['provider_type'],
            'category' => $providerData['category'],
            'provider_code' => $providerData['provider_code'],
        ]);

        // 4. handleconfigurationdecrypt
        $mergedData['config'] = null;
        $mergedData['decryptedConfig'] = null;

        if (! empty($configData['config'])) {
            if ($decryptConfig) {
                // whenneeddecrypto clock,settingalreadydecryptconfiguration(not脱敏)
                // need new 两timeProviderConfigItemobject,因for setConfig methodwill操asoriginalobjectconduct脱敏
                $mergedData['decryptedConfig'] = new ProviderConfigItem($configData['config']);
            }
            // config field set methodwill脱敏
            $mergedData['config'] = new ProviderConfigItem($configData['config']);
        }

        // 5. handletranslatefield
        $configTranslate = $providerConfigEntity->getTranslate() ?: [];
        $providerTranslate = $providerEntity->getTranslate() ?: [];
        $mergedData['translate'] = array_merge($configTranslate, $providerTranslate);
        return new ProviderConfigDTO($mergedData);
    }

    /**
     * according toorganizationandservicequotienttypegetservicequotientconfigurationcolumntable.
     * @param string $organizationCode organizationencoding
     * @param Category $category servicequotienttype
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProvidersModelsByCategory(string $organizationCode, Category $category): array
    {
        return $this->providerConfigRepository->getOrganizationProviders($organizationCode, $category);
    }

    /**
     * vlm 连通propertytest. llm/嵌入in app layer.
     * @throws Exception
     */
    public function vlmConnectivityTest(string $serviceProviderConfigId, string $modelVersion, string $organizationCode): ConnectResponse
    {
        // vml needdecryptconfiguration
        $serviceProviderConfigDTO = $this->getServiceProviderConfigDetail($serviceProviderConfigId, $organizationCode, true);
        $serviceProviderConfig = $serviceProviderConfigDTO->getDecryptedConfig();
        if (! $serviceProviderConfig) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        $serviceProviderCode = $serviceProviderConfigDTO->getProviderCode();
        if (! $serviceProviderCode) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }
        $provider = ServiceProviderFactory::get($serviceProviderCode, $serviceProviderConfigDTO->getCategory());
        return $provider->connectivityTestByModel($serviceProviderConfig, $modelVersion);
    }

    /**
     * getservicequotientconfiguration(综合method)
     * according tomodelversion,modelIDandorganizationencodinggetservicequotientconfiguration.
     *
     * @param string $modelOriginId modelversion
     * @param string $modelId modelID
     * @param string $organizationCode organizationencoding
     * @return ?ProviderConfigEntity servicequotientconfigurationresponse
     * @throws Exception
     */
    public function getServiceProviderConfig(
        string $modelOriginId,
        string $modelId,
        string $organizationCode,
        bool $throw = true,
    ): ?ProviderConfigEntity {
        // 1. ifprovide modelId,走newlogic
        if (! empty($modelId)) {
            return $this->getServiceProviderConfigByModelId($modelId, $organizationCode, $throw);
        }

        // 2. ifonly modelOriginId,先尝试findto应model
        if (! empty($modelOriginId)) {
            $models = $this->getModelsByVersionAndOrganization($modelOriginId, $organizationCode);
            if (! empty($models)) {
                // if找tomodel,not直接return官方servicequotientconfiguration,whileisconductenterone步judge
                $this->logger->info('找toto应model,judgeservicequotientconfiguration', [
                    'modelVersion' => $modelOriginId,
                    'organizationCode' => $organizationCode,
                ]);

                // fromactivatemodelmiddlefindcanuseservicequotientconfiguration
                return $this->findAvailableServiceProviderFromModels($models);
            }
        }

        // 3. ifallnot找to,throwexception
        ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
    }

    /**
     * according tomodelIDgetservicequotientconfiguration.
     * @param string $modelId modelID
     * @param string $organizationCode organizationencoding
     * @throws Exception
     */
    public function getServiceProviderConfigByModelId(string $modelId, string $organizationCode, bool $throwModelNotExist = true): ?ProviderConfigEntity
    {
        // 1. getmodelinfo
        $dataIsolation = ProviderDataIsolation::create($organizationCode);
        try {
            $serviceProviderModelEntity = $this->providerModelRepository->getById($dataIsolation, $modelId);
        } catch (Throwable) {
            if ($throwModelNotExist) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
            }
            return null;
        }

        // 2. checkmodelstatus
        if ($serviceProviderModelEntity->getStatus() === Status::Disabled) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotActive);
        }

        if ($serviceProviderModelEntity->getIsOffice()) {
            // get父levelmodelservicequotient id
            $serviceProviderConfigId = $this->getModelById((string) $serviceProviderModelEntity->getModelParentId())->getServiceProviderConfigId();
        } else {
            $serviceProviderConfigId = $serviceProviderModelEntity->getServiceProviderConfigId();
        }

        // 3. getservicequotientconfiguration
        $serviceProviderConfigEntity = $this->providerConfigRepository->getById($dataIsolation, $serviceProviderConfigId);
        if ($serviceProviderConfigEntity === null) {
            return null;
        }
        // 4. getservicequotientinfo
        $serviceProviderId = $serviceProviderConfigEntity->getServiceProviderId();
        $serviceProviderEntity = $this->serviceProviderRepository->getById($serviceProviderId);
        if ($serviceProviderEntity === null) {
            return null;
        }
        // 5. judgeservicequotienttypeandstatus
        $serviceProviderType = $serviceProviderEntity->getProviderType();
        if (
            $serviceProviderType !== ProviderType::Official
            && $serviceProviderConfigEntity->getStatus() === Status::Disabled
        ) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotActive);
        }
        $serviceProviderConfigEntity->getConfig()->setModelVersion($serviceProviderModelEntity->getModelVersion());
        return $serviceProviderConfigEntity;
    }

    public function getModelById(string $id): ProviderModelEntity
    {
        $dataIsolation = ProviderDataIsolation::create();
        return $this->providerModelRepository->getById($dataIsolation, $id);
    }

    /**
     * returnmodelandservicequotientallbeactivate接入pointcolumntable.
     * wantjudge model_parent_id modelandservicequotientwhetheractivate.
     * @return ProviderModelEntity[]
     */
    public function getOrganizationActiveModelsByIdOrType(string $key, string $orgCode): array
    {
        // createdataisolationobjectandgetcanusemodel
        $dataIsolation = ProviderDataIsolation::create($orgCode);
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // according tokeyconductfilter
        $models = [];
        foreach ($allModels as $model) {
            // filterdisable
            if ($model->getStatus() === Status::Disabled) {
                continue;
            }
            if (is_numeric($key)) {
                // 按IDfilter
                if ((string) $model->getId() === $key) {
                    $models[] = $model;
                }
            } elseif ($model->getModelId() === $key) {
                $models[] = $model;
            }
        }
        if (empty($models)) {
            return [];
        }
        return $models;
    }

    /**
     * get超清修复servicequotientconfiguration.
     * fromImageGenerateModelType::getMiracleVisionModes()[0]getmodel.
     * if官方andnon官方allenable,优先usenon官方configuration.
     *
     * @param string $modelId modelversion
     * @param string $organizationCode organizationencoding
     * @return ProviderConfigEntity servicequotientconfigurationresponse
     */
    public function getMiracleVisionServiceProviderConfig(string $modelId, string $organizationCode): ProviderConfigEntity
    {
        // createdataisolationobject
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // get所havecategorycanusemodel
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // 按model_idfilter
        $models = [];
        foreach ($allModels as $model) {
            if ($model->getModelId() === $modelId) {
                $models[] = $model;
            }
        }

        if (empty($models)) {
            $this->logger->warning('美graphmodelnot找to' . $modelId);
            // ifnothave找tomodel,throwexception
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
        }

        // 收collection所haveactivatemodel
        $activeModels = [];
        foreach ($models as $model) {
            if ($model->getStatus() === Status::Enabled) {
                $activeModels[] = $model;
            }
        }

        // ifnothaveactivatemodel,throwexception
        if (empty($activeModels)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotActive);
        }

        // fromactivatemodelmiddlefindcanuseservicequotientconfiguration
        return $this->findAvailableServiceProviderFromModels($activeModels);
    }

    /**
     * get所havenon官方servicequotientcolumntable,notdependencyatorganizationencoding
     *
     * @param Category $category servicequotientcategory别
     * @return ProviderConfigModelsDTO[]
     */
    public function getAllNonOfficialProviders(Category $category): array
    {
        $serviceProviderEntities = $this->serviceProviderRepository->getNonOfficialByCategory($category);
        return ProviderAssembler::toDTOs($serviceProviderEntities);
    }

    /**
     * get所havecanuseservicequotientcolumntable(include官方servicequotient),notdependencyatorganizationencoding.
     *
     * @param Category $category servicequotientcategory别
     * @return ProviderConfigModelsDTO[]
     */
    public function getAllAvailableProviders(Category $category): array
    {
        $serviceProviderEntities = $this->serviceProviderRepository->getByCategory($category);
        return ProviderAssembler::toDTOs($serviceProviderEntities);
    }

    /**
     * @return ProviderModelEntity[]
     */
    public function getOfficeModels(Category $category): array
    {
        $officeOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
        $providerDataIsolation = ProviderDataIsolation::create($officeOrganizationCode);
        return $this->providerModelRepository->getModelsForOrganization($providerDataIsolation, $category);
    }

    /**
     * get官方activatemodelconfiguration(supportreturn多).
     * @param string $modelOriginId model
     * @return ProviderConfigItem[] servicequotientconfigurationarray
     */
    public function getOfficeAndActiveModel(string $modelOriginId, Category $category): array
    {
        $serviceProviderEntities = $this->serviceProviderRepository->getByCategory($category);
        $serviceProviderConfigEntities = $this->providerConfigRepository->getsByServiceProviderIdsAndOffice(array_column($serviceProviderEntities, 'id'));

        $filteredModels = $this->getModelsByVersionAndOrganization($modelOriginId, OfficialOrganizationUtil::getOfficialOrganizationCode());

        if (empty($filteredModels)) {
            // ifnothave找tomatchactivatemodel,returnnullarray
            return [];
        }

        // createconfigurationIDtoconfiguration实bodymapping,便atfastspeedfind
        $configMap = [];
        foreach ($serviceProviderConfigEntities as $configEntity) {
            $configMap[$configEntity->getId()] = $configEntity;
        }

        // 收collection所havematchservicequotientconfiguration
        $result = [];
        foreach ($filteredModels as $activeModel) {
            $targetConfigId = $activeModel->getServiceProviderConfigId();
            if (isset($configMap[$targetConfigId])) {
                $config = $configMap[$targetConfigId]->getConfig();
                if ($config) {
                    $config->setModelVersion($activeModel->getModelVersion());
                    $config->setProviderModelId((string) $activeModel->getId());
                    $result[] = $config;
                }
            }
        }

        // ifnothave找toanyvalidconfiguration,returnnullarray
        return $result;
    }

    /**
     * Get be delightful display models and Delightful provider models visible to current organization.
     * @param string $organizationCode Organization code
     * @return ProviderModelEntity[]
     */
    public function getBeDelightfulDisplayModelsForOrganization(string $organizationCode): array
    {
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // get所havecategorycanusemodel
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // 按be_delightful_display_statefilter
        $models = [];
        foreach ($allModels as $model) {
            if ($model->isBeDelightfulDisplayState() === 1) {
                $models[] = $model;
            }
        }

        $beDelightfulModels = [];
        foreach ($models as $model) {
            $modelConfig = $model->getConfig();
            if (! $modelConfig || ! $modelConfig->isSupportFunction()) {
                continue;
            }
            $beDelightfulModels[] = $model;
        }

        // according to modelId go重
        $uniqueModels = [];
        foreach ($beDelightfulModels as $model) {
            $uniqueModels[$model->getModelId()] = $model;
        }

        // according to sort sort,bigtosmall
        usort($uniqueModels, static function ($a, $b) {
            return $b->getSort() <=> $a->getSort();
        });

        return $uniqueModels;
    }

    public function queriesModels(ProviderDataIsolation $dataIsolation, ProviderModelQuery $providerModelQuery): array
    {
        $providerModelEntities = $this->providerModelRepository->getModelsForOrganization($dataIsolation, $providerModelQuery->getCategory(), $providerModelQuery->getStatus());

        // modelId 经passfilter,go重选one
        if ($providerModelQuery->isModelIdFilter()) {
            $uniqueModels = [];
            foreach ($providerModelEntities as $model) {
                $modelId = $model->getModelId();
                // ifthis modelId alsonothavebeadd,thenadd
                if (! isset($uniqueModels[$modelId])) {
                    $uniqueModels[$modelId] = $model;
                }
            }
            $providerModelEntities = array_values($uniqueModels);
        }

        return $providerModelEntities;
    }

    /**
     * initializeDelightfulservicequotientconfigurationdata.
     */
    public function initializeDelightfulProviderConfigs(): int
    {
        $count = 0;
        $categories = [Category::LLM, Category::VLM];
        $officialOrganization = OfficialOrganizationUtil::getOfficialOrganizationCode();

        foreach ($categories as $category) {
            // findprovider_code=Officialservicequotient
            $provider = $this->serviceProviderRepository->getByCodeAndCategory(
                ProviderCode::Official,
                $category
            );

            if ($provider === null) {
                $this->logger->warning('not找toOfficialservicequotient', ['category' => $category->value]);
                continue;
            }

            // createdataisolationobject
            $dataIsolation = ProviderDataIsolation::create($officialOrganization);

            // judgetheservicequotientwhetheralreadyhaveconfiguration
            $existingConfig = $this->providerConfigRepository->findFirstByServiceProviderId(
                $dataIsolation,
                $provider->getId()
            );

            if ($existingConfig !== null) {
                $this->logger->info('servicequotientconfigurationalready存in,skip', [
                    'category' => $category->value,
                    'provider_id' => $provider->getId(),
                ]);
                continue;
            }

            // createconfiguration实body
            $configEntity = new ProviderConfigEntity();
            $configEntity->setServiceProviderId($provider->getId());
            $configEntity->setOrganizationCode($officialOrganization);
            $configEntity->setStatus(Status::Disabled);
            $configEntity->setConfig(null);

            // saveconfiguration
            $this->providerConfigRepository->save($dataIsolation, $configEntity);
            ++$count;

            $this->logger->info('createservicequotientconfigurationsuccess', [
                'category' => $category->value,
                'provider_id' => $provider->getId(),
            ]);
        }

        return $count;
    }

    /**
     * fromactivatemodelmiddlefindcanuseservicequotientconfiguration
     * 优先returnnon官方configuration,ifnothavethenreturn官方configuration.
     *
     * @param ProviderModelEntity[] $activeModels activatemodelcolumntable
     */
    private function findAvailableServiceProviderFromModels(array $activeModels): ProviderConfigEntity
    {
        if (empty($activeModels)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotActive);
        }

        // 1. 收collection所haveneedquery serviceProviderConfigId
        $configIds = [];
        foreach ($activeModels as $model) {
            $configIds[] = $model->getServiceProviderConfigId();
        }
        $configIds = array_unique($configIds);

        // 2. batchquantityquery所haveconfiguration
        $configMap = $this->providerConfigRepository->getByIdsWithoutOrganizationFilter($configIds);

        // 3. 收collection所haveneedquery serviceProviderId
        $providerIds = [];
        foreach ($configMap as $config) {
            $providerIds[] = $config->getServiceProviderId();
        }
        $providerIds = array_unique($providerIds);

        // 4. batchquantityquery所haveservicequotient
        $providerMap = $this->serviceProviderRepository->getByIds($providerIds);

        // 5. 重建优先levelhandlelogic
        $officialConfig = null;

        foreach ($activeModels as $model) {
            $configId = $model->getServiceProviderConfigId();

            // checkconfigurationwhether存in
            if (! isset($configMap[$configId])) {
                continue;
            }
            $serviceProviderConfigEntity = $configMap[$configId];
            $serviceProviderConfigEntity->getConfig()->setModelVersion($model->getModelVersion());
            // checkservicequotientwhether存in
            $serviceProviderId = $serviceProviderConfigEntity->getServiceProviderId();
            if (! isset($providerMap[$serviceProviderId])) {
                continue;
            }
            $serviceProviderEntity = $providerMap[$serviceProviderId];

            // getservicequotienttype
            $providerType = $serviceProviderEntity->getProviderType();

            // toatnon官方servicequotient,checkitswhetheractivate
            if ($providerType !== ProviderType::Official) {
                // ifisnon官方servicequotientbutnotactivate,thenskip
                if ($serviceProviderConfigEntity->getStatus() !== Status::Enabled) {
                    continue;
                }
                // 找toactivatenon官方configuration,immediatelyreturn(优先levelmosthigh)
                return $serviceProviderConfigEntity;
            }

            // ifis官方servicequotientconfiguration,先save,ifnothave找tonon官方againuse
            if ($officialConfig === null) {
                $officialConfig = $serviceProviderConfigEntity;
            }
        }

        // if找to官方configuration,thenreturn
        if ($officialConfig !== null) {
            return $officialConfig;
        }

        // if官方andnon官方allnothave找toactivateconfiguration,throwexception
        ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotActive);
    }

    /**
     * according tomodelversionandorganizationgetmodelcolumntable.
     * @param string $modelOriginId modelid
     * @param string $organizationCode organizationcode
     * @return ProviderModelEntity[] filterbackmodelcolumntable
     */
    private function getModelsByVersionAndOrganization(string $modelOriginId, string $organizationCode): array
    {
        // createdataisolationobject
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // get所havecategorycanusemodel
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // 按model_versionfilter
        $filteredModels = [];
        foreach ($allModels as $model) {
            if ($model->getModelId() === $modelOriginId) {
                $filteredModels[] = $model;
            }
        }

        return $filteredModels;
    }
}
