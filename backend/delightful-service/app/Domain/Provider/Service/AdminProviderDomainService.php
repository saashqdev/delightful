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
     * getservice商configurationinfo.
     */
    public function getServiceProviderConfigDetail(string $serviceProviderConfigId, string $organizationCode, bool $decryptConfig = false): ProviderConfigDTO
    {
        // 1. getservice商configuration实体
        $providerConfigEntity = $this->providerConfigRepository->getProviderConfigEntityById($serviceProviderConfigId, $organizationCode);

        if ($providerConfigEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 2. getservice商info
        $providerEntity = $this->serviceProviderRepository->getById($providerConfigEntity->getServiceProviderId());

        if ($providerEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 3. group装 ProviderConfigDTO
        $configData = $providerConfigEntity->toArray();
        $providerData = $providerEntity->toArray();
        // mergeconfiguration和service商data
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
                // 当needdecrypt时，setting已decrypt的configuration（不脱敏)
                // need new 两次ProviderConfigItemobject，因为 setConfig methodwill操作originalobject进行脱敏
                $mergedData['decryptedConfig'] = new ProviderConfigItem($configData['config']);
            }
            // config field的 set methodwill脱敏
            $mergedData['config'] = new ProviderConfigItem($configData['config']);
        }

        // 5. handle翻译field
        $configTranslate = $providerConfigEntity->getTranslate() ?: [];
        $providerTranslate = $providerEntity->getTranslate() ?: [];
        $mergedData['translate'] = array_merge($configTranslate, $providerTranslate);
        return new ProviderConfigDTO($mergedData);
    }

    /**
     * according toorganization和service商typegetservice商configuration列表.
     * @param string $organizationCode organizationencoding
     * @param Category $category service商type
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProvidersModelsByCategory(string $organizationCode, Category $category): array
    {
        return $this->providerConfigRepository->getOrganizationProviders($organizationCode, $category);
    }

    /**
     * vlm 的连通性test. llm/嵌入的在 app 层。
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
     * getservice商configuration（综合method）
     * according tomodelversion、modelID和organizationencodinggetservice商configuration.
     *
     * @param string $modelOriginId modelversion
     * @param string $modelId modelID
     * @param string $organizationCode organizationencoding
     * @return ?ProviderConfigEntity service商configurationresponse
     * @throws Exception
     */
    public function getServiceProviderConfig(
        string $modelOriginId,
        string $modelId,
        string $organizationCode,
        bool $throw = true,
    ): ?ProviderConfigEntity {
        // 1. 如果提供了 modelId，走new逻辑
        if (! empty($modelId)) {
            return $this->getServiceProviderConfigByModelId($modelId, $organizationCode, $throw);
        }

        // 2. 如果只有 modelOriginId，先尝试查找对应的model
        if (! empty($modelOriginId)) {
            $models = $this->getModelsByVersionAndOrganization($modelOriginId, $organizationCode);
            if (! empty($models)) {
                // 如果找到model，不直接return官方service商configuration，而是进行进一步判断
                $this->logger->info('找到对应model，判断service商configuration', [
                    'modelVersion' => $modelOriginId,
                    'organizationCode' => $organizationCode,
                ]);

                // 从activate的model中查找可用的service商configuration
                return $this->findAvailableServiceProviderFromModels($models);
            }
        }

        // 3. 如果都没找到，throwexception
        ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
    }

    /**
     * according tomodelIDgetservice商configuration.
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
            // get父级的modelservice商 id
            $serviceProviderConfigId = $this->getModelById((string) $serviceProviderModelEntity->getModelParentId())->getServiceProviderConfigId();
        } else {
            $serviceProviderConfigId = $serviceProviderModelEntity->getServiceProviderConfigId();
        }

        // 3. getservice商configuration
        $serviceProviderConfigEntity = $this->providerConfigRepository->getById($dataIsolation, $serviceProviderConfigId);
        if ($serviceProviderConfigEntity === null) {
            return null;
        }
        // 4. getservice商info
        $serviceProviderId = $serviceProviderConfigEntity->getServiceProviderId();
        $serviceProviderEntity = $this->serviceProviderRepository->getById($serviceProviderId);
        if ($serviceProviderEntity === null) {
            return null;
        }
        // 5. 判断service商type和status
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
     * returnmodel和service商都被activate了的接入点列表.
     * 要判断 model_parent_id 的model和service商是否activate.
     * @return ProviderModelEntity[]
     */
    public function getOrganizationActiveModelsByIdOrType(string $key, string $orgCode): array
    {
        // createdata隔离object并get可用model
        $dataIsolation = ProviderDataIsolation::create($orgCode);
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // according tokey进行filter
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
     * get超清修复service商configuration。
     * 从ImageGenerateModelType::getMiracleVisionModes()[0]getmodel。
     * 如果官方和非官方都enable，优先use非官方configuration。
     *
     * @param string $modelId modelversion
     * @param string $organizationCode organizationencoding
     * @return ProviderConfigEntity service商configurationresponse
     */
    public function getMiracleVisionServiceProviderConfig(string $modelId, string $organizationCode): ProviderConfigEntity
    {
        // createdata隔离object
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // get所有category的可用model
        $allModels = $this->providerModelRepository->getModelsForOrganization($dataIsolation);

        // 按model_idfilter
        $models = [];
        foreach ($allModels as $model) {
            if ($model->getModelId() === $modelId) {
                $models[] = $model;
            }
        }

        if (empty($models)) {
            $this->logger->warning('美图model未找到' . $modelId);
            // 如果没有找到model，throwexception
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
        }

        // 收集所有activate的model
        $activeModels = [];
        foreach ($models as $model) {
            if ($model->getStatus() === Status::Enabled) {
                $activeModels[] = $model;
            }
        }

        // 如果没有activate的model，throwexception
        if (empty($activeModels)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotActive);
        }

        // 从activate的model中查找可用的service商configuration
        return $this->findAvailableServiceProviderFromModels($activeModels);
    }

    /**
     * get所有非官方service商列表，不dependency于organizationencoding
     *
     * @param Category $category service商类别
     * @return ProviderConfigModelsDTO[]
     */
    public function getAllNonOfficialProviders(Category $category): array
    {
        $serviceProviderEntities = $this->serviceProviderRepository->getNonOfficialByCategory($category);
        return ProviderAssembler::toDTOs($serviceProviderEntities);
    }

    /**
     * get所有可用的service商列表（include官方service商），不dependency于organizationencoding.
     *
     * @param Category $category service商类别
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
     * get官方的activatemodelconfiguration（supportreturn多个）.
     * @param string $modelOriginId model
     * @return ProviderConfigItem[] service商configurationarray
     */
    public function getOfficeAndActiveModel(string $modelOriginId, Category $category): array
    {
        $serviceProviderEntities = $this->serviceProviderRepository->getByCategory($category);
        $serviceProviderConfigEntities = $this->providerConfigRepository->getsByServiceProviderIdsAndOffice(array_column($serviceProviderEntities, 'id'));

        $filteredModels = $this->getModelsByVersionAndOrganization($modelOriginId, OfficialOrganizationUtil::getOfficialOrganizationCode());

        if (empty($filteredModels)) {
            // 如果没有找到匹配的activatemodel，returnnullarray
            return [];
        }

        // createconfigurationID到configuration实体的mapping，便于快速查找
        $configMap = [];
        foreach ($serviceProviderConfigEntities as $configEntity) {
            $configMap[$configEntity->getId()] = $configEntity;
        }

        // 收集所有匹配的service商configuration
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

        // 如果没有找到任何validconfiguration，returnnullarray
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

        // get所有category的可用model
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

        // according to modelId 去重
        $uniqueModels = [];
        foreach ($beDelightfulModels as $model) {
            $uniqueModels[$model->getModelId()] = $model;
        }

        // according to sort sort，大到小
        usort($uniqueModels, static function ($a, $b) {
            return $b->getSort() <=> $a->getSort();
        });

        return $uniqueModels;
    }

    public function queriesModels(ProviderDataIsolation $dataIsolation, ProviderModelQuery $providerModelQuery): array
    {
        $providerModelEntities = $this->providerModelRepository->getModelsForOrganization($dataIsolation, $providerModelQuery->getCategory(), $providerModelQuery->getStatus());

        // modelId 经过filter，去重选一个
        if ($providerModelQuery->isModelIdFilter()) {
            $uniqueModels = [];
            foreach ($providerModelEntities as $model) {
                $modelId = $model->getModelId();
                // 如果这个 modelId 还没有被添加，则添加
                if (! isset($uniqueModels[$modelId])) {
                    $uniqueModels[$modelId] = $model;
                }
            }
            $providerModelEntities = array_values($uniqueModels);
        }

        return $providerModelEntities;
    }

    /**
     * initializeDelightfulservice商configurationdata.
     */
    public function initializeDelightfulProviderConfigs(): int
    {
        $count = 0;
        $categories = [Category::LLM, Category::VLM];
        $officialOrganization = OfficialOrganizationUtil::getOfficialOrganizationCode();

        foreach ($categories as $category) {
            // 查找provider_code=Official的service商
            $provider = $this->serviceProviderRepository->getByCodeAndCategory(
                ProviderCode::Official,
                $category
            );

            if ($provider === null) {
                $this->logger->warning('未找到Officialservice商', ['category' => $category->value]);
                continue;
            }

            // createdata隔离object
            $dataIsolation = ProviderDataIsolation::create($officialOrganization);

            // 判断该service商是否已有configuration
            $existingConfig = $this->providerConfigRepository->findFirstByServiceProviderId(
                $dataIsolation,
                $provider->getId()
            );

            if ($existingConfig !== null) {
                $this->logger->info('service商configuration已存在，skip', [
                    'category' => $category->value,
                    'provider_id' => $provider->getId(),
                ]);
                continue;
            }

            // createconfiguration实体
            $configEntity = new ProviderConfigEntity();
            $configEntity->setServiceProviderId($provider->getId());
            $configEntity->setOrganizationCode($officialOrganization);
            $configEntity->setStatus(Status::Disabled);
            $configEntity->setConfig(null);

            // saveconfiguration
            $this->providerConfigRepository->save($dataIsolation, $configEntity);
            ++$count;

            $this->logger->info('createservice商configurationsuccess', [
                'category' => $category->value,
                'provider_id' => $provider->getId(),
            ]);
        }

        return $count;
    }

    /**
     * 从activate的model中查找可用的service商configuration
     * 优先return非官方configuration，如果没有则return官方configuration.
     *
     * @param ProviderModelEntity[] $activeModels activate的model列表
     */
    private function findAvailableServiceProviderFromModels(array $activeModels): ProviderConfigEntity
    {
        if (empty($activeModels)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotActive);
        }

        // 1. 收集所有needquery的 serviceProviderConfigId
        $configIds = [];
        foreach ($activeModels as $model) {
            $configIds[] = $model->getServiceProviderConfigId();
        }
        $configIds = array_unique($configIds);

        // 2. 批量query所有configuration
        $configMap = $this->providerConfigRepository->getByIdsWithoutOrganizationFilter($configIds);

        // 3. 收集所有needquery的 serviceProviderId
        $providerIds = [];
        foreach ($configMap as $config) {
            $providerIds[] = $config->getServiceProviderId();
        }
        $providerIds = array_unique($providerIds);

        // 4. 批量query所有service商
        $providerMap = $this->serviceProviderRepository->getByIds($providerIds);

        // 5. 重建优先级handle逻辑
        $officialConfig = null;

        foreach ($activeModels as $model) {
            $configId = $model->getServiceProviderConfigId();

            // checkconfiguration是否存在
            if (! isset($configMap[$configId])) {
                continue;
            }
            $serviceProviderConfigEntity = $configMap[$configId];
            $serviceProviderConfigEntity->getConfig()->setModelVersion($model->getModelVersion());
            // checkservice商是否存在
            $serviceProviderId = $serviceProviderConfigEntity->getServiceProviderId();
            if (! isset($providerMap[$serviceProviderId])) {
                continue;
            }
            $serviceProviderEntity = $providerMap[$serviceProviderId];

            // getservice商type
            $providerType = $serviceProviderEntity->getProviderType();

            // 对于非官方service商，check其是否activate
            if ($providerType !== ProviderType::Official) {
                // 如果是非官方service商但未activate，则skip
                if ($serviceProviderConfigEntity->getStatus() !== Status::Enabled) {
                    continue;
                }
                // 找到activate的非官方configuration，立即return（优先级最高）
                return $serviceProviderConfigEntity;
            }

            // 如果是官方service商configuration，先save，如果没有找到非官方的再use
            if ($officialConfig === null) {
                $officialConfig = $serviceProviderConfigEntity;
            }
        }

        // 如果找到了官方configuration，则return
        if ($officialConfig !== null) {
            return $officialConfig;
        }

        // 如果官方和非官方都没有找到activate的configuration，throwexception
        ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotActive);
    }

    /**
     * according tomodelversion和organizationgetmodel列表.
     * @param string $modelOriginId modelid
     * @param string $organizationCode organizationcode
     * @return ProviderModelEntity[] filter后的model列表
     */
    private function getModelsByVersionAndOrganization(string $modelOriginId, string $organizationCode): array
    {
        // createdata隔离object
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // get所有category的可用model
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
