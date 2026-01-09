<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\ModelGateway\Mapper;

use App\Domain\File\Service\FileDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ModelType;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\Infrastructure\Core\Contract\Model\RerankInterface;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;
use App\Infrastructure\Core\Model\ImageGenerationModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageModel;
use App\Infrastructure\ExternalAPI\DelightfulAIApi\DelightfulAILocalModel;
use DateTime;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Api\RequestOptions\ApiOptions;
use Hyperf\Odin\Contract\Model\EmbeddingInterface;
use Hyperf\Odin\Contract\Model\ModelInterface;
use Hyperf\Odin\Factory\ModelFactory;
use Hyperf\Odin\Model\AbstractModel;
use Hyperf\Odin\Model\ModelOptions;
use Hyperf\Odin\ModelMapper;
use InvalidArgumentException;
use Throwable;

/**
 * 集合project本身多套的 ModelGatewayMapper - finalall部convert为 odin model parameterformat.
 */
class ModelGatewayMapper extends ModelMapper
{
    /**
     * 持久化的customizedata.
     * @var array<string, OdinModelAttributes>
     */
    protected array $attributes = [];

    /**
     * @var array<string, RerankInterface>
     */
    protected array $rerank = [];

    private ProviderManager $providerManager;

    public function __construct(protected ConfigInterface $config, LoggerFactory $loggerFactory)
    {
        $this->providerManager = di(ProviderManager::class);
        $logger = $loggerFactory->get('ModelGatewayMapper');
        $this->models['chat'] = [];
        $this->models['embedding'] = [];
        parent::__construct($config, $logger);

        $this->loadEnvModels();
    }

    public function exists(BaseDataIsolation $dataIsolation, string $model): bool
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        if (isset($this->models['chat'][$model]) || isset($this->models['embedding'][$model])) {
            return true;
        }
        return (bool) $this->getByAdmin($dataIsolation, $model);
    }

    public function getOfficialChatModelProxy(string $model): DelightfulAILocalModel
    {
        $dataIsolation = ModelGatewayDataIsolation::create('', '');
        $dataIsolation->setCurrentOrganizationCode($dataIsolation->getOfficialOrganizationCode());
        return $this->getChatModelProxy($dataIsolation, $model, true);
    }

    /**
     * 内部use chat 时，一定是use该method.
     * will自动替代为本地代理model.
     */
    public function getChatModelProxy(BaseDataIsolation $dataIsolation, string $model, bool $useOfficialAccessToken = false): DelightfulAILocalModel
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        $odinModel = $this->getOrganizationChatModel($dataIsolation, $model);
        if ($odinModel instanceof OdinModel) {
            $odinModel = $odinModel->getModel();
        }
        if (! $odinModel instanceof AbstractModel) {
            throw new InvalidArgumentException(sprintf('Model %s is not a valid Odin model.', $model));
        }
        return $this->createProxy($dataIsolation, $model, $odinModel->getModelOptions(), $odinModel->getApiRequestOptions(), $useOfficialAccessToken);
    }

    /**
     * 内部use embedding 时，一定是use该method.
     * will自动替代为本地代理model.
     */
    public function getEmbeddingModelProxy(BaseDataIsolation $dataIsolation, string $model): DelightfulAILocalModel
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        /** @var AbstractModel $odinModel */
        $odinModel = $this->getOrganizationEmbeddingModel($dataIsolation, $model);
        if ($odinModel instanceof OdinModel) {
            $odinModel = $odinModel->getModel();
        }
        if (! $odinModel instanceof AbstractModel) {
            throw new InvalidArgumentException(sprintf('Model %s is not a valid Odin model.', $model));
        }
        // convert为代理
        return $this->createProxy($dataIsolation, $model, $odinModel->getModelOptions(), $odinModel->getApiRequestOptions());
    }

    /**
     * 该methodgetto的一定是真实call的model.
     * 仅 ModelGateway 领域use.
     * @param string $model expected是管理后台的 model_id，过度阶段接受传入 model_version
     */
    public function getOrganizationChatModel(BaseDataIsolation $dataIsolation, string $model): ModelInterface|OdinModel
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        $odinModel = $this->getByAdmin($dataIsolation, $model, ModelType::LLM);
        if ($odinModel) {
            return $odinModel;
        }
        return $this->getChatModel($model);
    }

    /**
     * 该methodgetto的一定是真实call的model.
     * 仅 ModelGateway 领域use.
     * @param string $model modelname expected是管理后台的 model_id，过度阶段接受 model_version
     */
    public function getOrganizationEmbeddingModel(BaseDataIsolation $dataIsolation, string $model): EmbeddingInterface|OdinModel
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        $odinModel = $this->getByAdmin($dataIsolation, $model, ModelType::EMBEDDING);
        if ($odinModel) {
            return $odinModel;
        }
        return $this->getEmbeddingModel($model);
    }

    public function getOrganizationImageModel(BaseDataIsolation $dataIsolation, string $model): ?ImageModel
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        $result = $this->getByAdmin($dataIsolation, $model);

        // 只return ImageGenerationModelWrapper type的result
        if ($result instanceof ImageModel) {
            return $result;
        }

        return null;
    }

    /**
     * getcurrentorganization下的所have可use chat model.
     * @return OdinModel[]
     */
    public function getChatModels(BaseDataIsolation $dataIsolation): array
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        return $this->getModelsByType($dataIsolation, ModelType::LLM);
    }

    /**
     * getcurrentorganization下的所have可use embedding model.
     * @return OdinModel[]
     */
    public function getEmbeddingModels(BaseDataIsolation $dataIsolation): array
    {
        $dataIsolation = ModelGatewayDataIsolation::createByBaseDataIsolation($dataIsolation);
        return $this->getModelsByType($dataIsolation, ModelType::EMBEDDING);
    }

    /**
     * get all available image models under the current organization.
     * @return OdinModel[]
     */
    public function getImageModels(BaseDataIsolation $dataIsolation): array
    {
        $serviceProviderDomainService = di(AdminProviderDomainService::class);
        $officeModels = $serviceProviderDomainService->getOfficeModels(Category::VLM);

        $odinModels = [];
        foreach ($officeModels as $model) {
            $key = $model->getModelId();

            // Create virtual image generation model
            $imageModel = new ImageGenerationModel(
                $model->getModelId(),
                [], // Empty config array
                $this->logger
            );

            // Create model attributes
            $attributes = new OdinModelAttributes(
                key: $key,
                name: $model->getModelVersion(),
                label: $model->getName() ?: 'Image Generation',
                icon: $model->getIcon() ?: '',
                tags: [['type' => 1, 'value' => 'Image Generation']],
                createdAt: $model->getCreatedAt() ?? new DateTime(),
                owner: 'DelightfulAI',
                providerAlias: '',
                providerModelId: (string) $model->getId(),
                description: $model->getLocalizedDescription($dataIsolation->getLanguage()) ?? '',
            );

            // Create OdinModel
            $odinModel = new OdinModel($key, $imageModel, $attributes);
            $odinModels[$key] = $odinModel;
        }

        return $odinModels;
    }

    protected function loadEnvModels(): void
    {
        // env 添加的model增加上 attributes
        /**
         * @var string $name
         * @var AbstractModel $model
         */
        foreach ($this->models['chat'] as $name => $model) {
            $key = $name;
            $this->attributes[$key] = new OdinModelAttributes(
                key: $key,
                name: $name,
                label: $name,
                icon: '',
                tags: [['type' => 1, 'value' => 'DelightfulAI']],
                createdAt: new DateTime(),
                owner: 'DelightfulOdin',
                description: '',
            );
            $this->logger->info('EnvModelRegister', [
                'key' => $name,
                'model' => $model->getModelName(),
                'implementation' => get_class($model),
            ]);
        }
        foreach ($this->models['embedding'] as $name => $model) {
            $key = $name;
            $this->attributes[$key] = new OdinModelAttributes(
                key: $key,
                name: $name,
                label: $name,
                icon: '',
                tags: [['type' => 1, 'value' => 'DelightfulAI']],
                createdAt: new DateTime(),
                owner: 'DelightfulOdin',
                description: '',
            );
            $this->logger->info('EnvModelRegister', [
                'key' => $name,
                'model' => $model->getModelName(),
                'implementation' => get_class($model),
                'vector_size' => $model->getModelOptions()->getVectorSize(),
            ]);
        }
    }

    /**
     * getcurrentorganization下指定type的所have可usemodel.
     * @return OdinModel[]
     */
    private function getModelsByType(ModelGatewayDataIsolation $dataIsolation, ModelType $modelType): array
    {
        $list = [];

        // get已持久化的configuration
        $models = $this->getModels($modelType->isLLM() ? 'chat' : 'embedding');
        foreach ($models as $name => $model) {
            switch ($modelType) {
                case ModelType::LLM:
                    if ($model instanceof AbstractModel && ! $model->getModelOptions()->isChat()) {
                        continue 2;
                    }
                    break;
                case ModelType::EMBEDDING:
                    if ($model instanceof AbstractModel && ! $model->getModelOptions()->isEmbedding()) {
                        continue 2;
                    }
                    break;
                default:
                    // ifnothave指定type，thenall部添加
                    break;
            }
            $list[$name] = new OdinModel(key: $name, model: $model, attributes: $this->attributes[$name]);
        }

        // getcurrent套餐下的可usemodel
        $availableModelIds = $dataIsolation->getSubscriptionManager()->getAvailableModelIds($modelType);

        // needcontain官方organization的data
        $providerDataIsolation = ProviderDataIsolation::createByBaseDataIsolation($dataIsolation);
        $providerDataIsolation->setContainOfficialOrganization(true);

        // load model
        $providerModels = $this->providerManager->getModelsByModelIds($providerDataIsolation, $availableModelIds, $modelType);

        $modelLogs = [];

        $providerConfigIds = [];
        foreach ($providerModels as $providerModel) {
            $providerConfigIds[] = $providerModel->getServiceProviderConfigId();
            $modelLogs[$providerModel->getModelId()] = [
                'model_id' => $providerModel->getModelId(),
                'provider_config_id' => (string) $providerModel->getServiceProviderConfigId(),
                'is_office' => $providerModel->isOffice(),
            ];
        }
        $providerConfigIds = array_unique($providerConfigIds);

        // load service商configuration
        $providerConfigs = $this->providerManager->getProviderConfigsByIds($providerDataIsolation, $providerConfigIds);
        $providerIds = [];
        foreach ($providerConfigs as $providerConfig) {
            $providerIds[] = $providerConfig->getServiceProviderId();
        }

        // get service商
        $providers = $this->providerManager->getProvidersByIds($providerDataIsolation, $providerIds);

        // group装data
        foreach ($providerModels as $providerModel) {
            if (! $providerConfig = $providerConfigs[$providerModel->getServiceProviderConfigId()] ?? null) {
                $modelLogs[$providerModel->getModelId()]['error'] = 'ProviderConfig not found';
                continue;
            }
            if (! $providerConfig->getStatus()->isEnabled()) {
                $modelLogs[$providerModel->getModelId()]['error'] = 'ProviderConfig disabled';
                continue;
            }
            if (! $provider = $providers[$providerConfig->getServiceProviderId()] ?? null) {
                $modelLogs[$providerModel->getModelId()]['error'] = 'Provider not found';
                continue;
            }
            $model = $this->createModelByProvider($providerDataIsolation, $providerModel, $providerConfig, $provider);
            if (! $model) {
                $modelLogs[$providerModel->getModelId()]['error'] = 'Model disabled or invalid';
                continue;
            }
            $list[$model->getAttributes()->getKey()] = $model;
        }

        // 按照 $availableModelIds sort
        if ($availableModelIds !== null) {
            $orderedList = [];
            foreach ($availableModelIds as $modelId) {
                if (isset($list[$modelId])) {
                    $orderedList[$modelId] = $list[$modelId];
                }
            }
            $list = $orderedList;
        }

        $this->logger->info('检索tomodel', $modelLogs);

        return $list;
    }

    private function createModelByProvider(
        ProviderDataIsolation $providerDataIsolation,
        ProviderModelEntity $providerModelEntity,
        ProviderConfigEntity $providerConfigEntity,
        ProviderEntity $providerEntity,
    ): null|ImageModel|OdinModel {
        if (! $providerDataIsolation->isOfficialOrganization() && (! $providerModelEntity->getStatus()->isEnabled() || ! $providerConfigEntity->getStatus()->isEnabled())) {
            return null;
        }

        $chat = false;
        $functionCall = false;
        $multiModal = false;
        $embedding = false;
        $vectorSize = 0;
        if ($providerModelEntity->getModelType()->isLLM()) {
            $chat = true;
            $functionCall = $providerModelEntity->getConfig()?->isSupportFunction();
            $multiModal = $providerModelEntity->getConfig()?->isSupportMultiModal();
        } elseif ($providerModelEntity->getModelType()->isEmbedding()) {
            $embedding = true;
            $vectorSize = $providerModelEntity->getConfig()?->getVectorSize();
        }

        $key = $providerModelEntity->getModelId();

        $implementation = $providerEntity->getProviderCode()->getImplementation();
        $providerConfigItem = $providerConfigEntity->getConfig();
        $implementationConfig = $providerEntity->getProviderCode()->getImplementationConfig($providerConfigItem, $providerModelEntity->getModelVersion());

        if ($providerEntity->getProviderType()->isCustom()) {
            // customizeservice商统一显示别名，ifnothave别名then显示“customizeservice商”（need考虑多语言）
            $providerName = $providerConfigEntity->getLocalizedAlias($providerDataIsolation->getLanguage());
        } else {
            // 内置service商的统一显示 service商name，notuse显示别名（need考虑多语言）
            $providerName = $providerEntity->getLocalizedName($providerDataIsolation->getLanguage());
        }

        // ifnot是官方organization，but是model是官方organization，统一显示 Delightful
        if (! $providerDataIsolation->isOfficialOrganization()
            && in_array($providerConfigEntity->getOrganizationCode(), $providerDataIsolation->getOfficialOrganizationCodes())) {
            $providerName = 'Delightful';
        }

        try {
            $fileDomainService = di(FileDomainService::class);
            // if是官方organization的 icon，切换官方organization
            if ($providerModelEntity->isOffice()) {
                $iconUrl = $fileDomainService->getLink($providerDataIsolation->getOfficialOrganizationCode(), $providerModelEntity->getIcon())?->getUrl() ?? '';
            } else {
                $iconUrl = $fileDomainService->getLink($providerModelEntity->getOrganizationCode(), $providerModelEntity->getIcon())?->getUrl() ?? '';
            }
        } catch (Throwable $e) {
            $iconUrl = '';
        }

        // according tomodeltypereturndifferent的package装object
        if ($providerModelEntity->getModelType()->isVLM()) {
            return new ImageModel($providerConfigItem->toArray(), $providerModelEntity->getModelVersion(), (string) $providerModelEntity->getId(), $providerEntity->getProviderCode());
        }

        // 对atLLM/Embeddingmodel，保持原have逻辑
        return new OdinModel(
            key: $key,
            model: $this->createModel($providerModelEntity->getModelVersion(), [
                'model' => $providerModelEntity->getModelVersion(),
                'implementation' => $implementation,
                'config' => $implementationConfig,
                'model_options' => [
                    'chat' => $chat,
                    'function_call' => $functionCall,
                    'embedding' => $embedding,
                    'multi_modal' => $multiModal,
                    'vector_size' => $vectorSize,
                    'max_tokens' => $providerModelEntity->getConfig()?->getMaxTokens(),
                    'max_output_tokens' => $providerModelEntity->getConfig()?->getMaxOutputTokens(),
                    'default_temperature' => $providerModelEntity->getConfig()?->getCreativity(),
                    'fixed_temperature' => $providerModelEntity->getConfig()?->getTemperature(),
                ],
            ]),
            attributes: new OdinModelAttributes(
                key: $key,
                name: $providerModelEntity->getModelId(),
                label: $providerModelEntity->getName(),
                icon: $iconUrl,
                tags: [['type' => 1, 'value' => "{$providerName}"]],
                createdAt: $providerEntity->getCreatedAt(),
                owner: 'DelightfulAI',
                providerAlias: $providerConfigEntity->getAlias() ?? $providerEntity->getName(),
                providerModelId: (string) $providerModelEntity->getId(),
                providerId: (string) $providerConfigEntity->getId(),
                modelType: $providerModelEntity->getModelType()->value,
                description: $providerModelEntity->getLocalizedDescription($providerDataIsolation->getLanguage()),
            )
        );
    }

    private function getByAdmin(ModelGatewayDataIsolation $dataIsolation, string $model, ?ModelType $modelType = null): null|ImageModel|OdinModel
    {
        $providerDataIsolation = ProviderDataIsolation::createByBaseDataIsolation($dataIsolation);
        $providerDataIsolation->setContainOfficialOrganization(true);

        $checkStatus = true;
        if ($dataIsolation->isOfficialOrganization()) {
            $checkStatus = false;
        }

        // getmodel
        $providerModelEntity = $this->providerManager->getAvailableByModelIdOrId($providerDataIsolation, $model, $checkStatus);
        if (! $providerModelEntity) {
            $this->logger->info('modelnot存in', ['model' => $model]);
            return null;
        }
        if (! $dataIsolation->isOfficialOrganization() && ! $providerModelEntity->getStatus()->isEnabled()) {
            $this->logger->info('modelbedisable', ['model' => $model]);
            return null;
        }

        // checkcurrent套餐whetherhave这个model的usepermission - 目前only LLM modelhave这个限制
        if ($providerModelEntity->getModelType()->isLLM()) {
            if (! $dataIsolation->isOfficialOrganization() && ! $dataIsolation->getSubscriptionManager()->isValidModelAvailable($providerModelEntity->getModelId(), $modelType)) {
                $this->logger->info('modelnotin可use名单', ['model' => $providerModelEntity->getModelId(), 'model_type' => $modelType?->value]);
                return null;
            }
        }

        // getconfiguration
        $providerConfigEntity = $this->providerManager->getProviderConfigsByIds($providerDataIsolation, [$providerModelEntity->getServiceProviderConfigId()])[$providerModelEntity->getServiceProviderConfigId()] ?? null;
        if (! $providerConfigEntity) {
            $this->logger->info('service商configurationnot存in', ['model' => $model, 'provider_config_id' => $providerModelEntity->getServiceProviderConfigId()]);
            return null;
        }
        if (! $dataIsolation->isOfficialOrganization() && ! $providerConfigEntity->getStatus()->isEnabled()) {
            $this->logger->info('service商configurationbedisable', ['model' => $model, 'provider_config_id' => $providerModelEntity->getServiceProviderConfigId()]);
            return null;
        }

        // getservice商
        $providerEntity = $this->providerManager->getProvidersByIds($providerDataIsolation, [$providerConfigEntity->getServiceProviderId()])[$providerConfigEntity->getServiceProviderId()] ?? null;

        if (! $providerEntity) {
            $this->logger->info('service商not存in', ['model' => $model, 'provider_id' => $providerConfigEntity->getServiceProviderId()]);
            return null;
        }

        return $this->createModelByProvider($providerDataIsolation, $providerModelEntity, $providerConfigEntity, $providerEntity);
    }

    private function createProxy(ModelGatewayDataIsolation $dataIsolation, string $model, ModelOptions $modelOptions, ApiOptions $apiOptions, bool $useOfficialAccessToken = false): DelightfulAILocalModel
    {
        // useModelFactorycreatemodel实例
        $odinModel = ModelFactory::create(
            DelightfulAILocalModel::class,
            $model,
            [
                'use_official_access_token' => $useOfficialAccessToken,
                'vector_size' => $modelOptions->getVectorSize(),
                'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
                'user_id' => $dataIsolation->getCurrentUserId(),
            ],
            $modelOptions,
            $apiOptions,
            $this->logger
        );
        if (! $odinModel instanceof DelightfulAILocalModel) {
            throw new InvalidArgumentException(sprintf('Implementation %s is not defined.', DelightfulAILocalModel::class));
        }
        return $odinModel;
    }
}
