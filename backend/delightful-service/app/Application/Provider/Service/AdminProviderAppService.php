<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Service;

use App\Application\ModelGateway\Service\LLMAppService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\ModelGateway\Entity\Dto\CompletionDTO;
use App\Domain\ModelGateway\Entity\Dto\EmbeddingsDTO;
use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\DTO\ProviderConfigModelsDTO;
use App\Domain\Provider\DTO\ProviderModelDetailDTO;
use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ModelType;
use App\Domain\Provider\Entity\ValueObject\NaturalLanguageProcessing;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Event\ProviderConfigCreatedEvent;
use App\Domain\Provider\Event\ProviderConfigUpdatedEvent;
use App\Domain\Provider\Event\ProviderModelCreatedEvent;
use App\Domain\Provider\Event\ProviderModelDeletedEvent;
use App\Domain\Provider\Event\ProviderModelUpdatedEvent;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\Domain\Provider\Service\ConnectivityTest\ConnectResponse;
use App\Domain\Provider\Service\ProviderConfigDomainService;
use App\Domain\Provider\Service\ProviderModelDomainService;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Agent\Assembler\FileAssembler;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Provider\Assembler\ProviderAdminAssembler;
use App\Interfaces\Provider\DTO\CreateProviderConfigRequest;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;
use App\Interfaces\Provider\DTO\UpdateProviderConfigRequest;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Odin\Api\Response\ChatCompletionResponse;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

readonly class AdminProviderAppService
{
    public function __construct(
        private ProviderConfigDomainService $providerConfigDomainService,
        private FileDomainService $fileDomainService,
        private ProviderModelDomainService $providerModelDomainService,
        private AdminProviderDomainService $adminProviderDomainService,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * according to服务商configurationID获取服务商详细info.
     */
    public function getProviderModelsByConfigId(
        DelightfulUserAuthorization $authorization,
        string $configId
    ): ?ProviderConfigModelsDTO {
        // build数据隔离object
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // pass领域层method一次性获取服务商、configuration和模型info
        $providerModels = $this->providerConfigDomainService->getProviderModelsByConfigId($dataIsolation, $configId);
        if ($providerModels === null) {
            return null;
        }

        // ProviderModelsDTO 已经contain所有need的数据，统一handle provider 和 models 的 icon 并return
        $this->processProviderAndModelsIcons($providerModels);
        return $providerModels;
    }

    public function updateProvider(
        DelightfulUserAuthorization $authorization,
        UpdateProviderConfigRequest $updateProviderConfigRequest
    ): ProviderConfigModelsDTO {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );
        $providerConfigEntity = ProviderAdminAssembler::updateRequestToEntity($updateProviderConfigRequest, $authorization->getOrganizationCode());

        $providerConfigEntity = $this->providerConfigDomainService->updateProviderConfig($dataIsolation, $providerConfigEntity);

        // 触发服务商configuration更新事件
        $this->eventDispatcher->dispatch(new ProviderConfigUpdatedEvent(
            $providerConfigEntity,
            $authorization->getOrganizationCode(),
            $dataIsolation->getLanguage()
        ));

        return ProviderAdminAssembler::entityToModelsDTO($providerConfigEntity);
    }

    public function createProvider(
        DelightfulUserAuthorization $authorization,
        CreateProviderConfigRequest $createProviderConfigRequest
    ): ProviderConfigModelsDTO {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );
        $providerConfigEntity = ProviderAdminAssembler::createRequestToEntity($createProviderConfigRequest, $authorization->getOrganizationCode());

        $providerConfigEntity = $this->providerConfigDomainService->createProviderConfig($dataIsolation, $providerConfigEntity);

        // 触发服务商configurationcreate事件
        $this->eventDispatcher->dispatch(new ProviderConfigCreatedEvent(
            $providerConfigEntity,
            $authorization->getOrganizationCode(),
            $dataIsolation->getLanguage()
        ));

        $providerEntity = $this->providerConfigDomainService->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());
        if ($providerEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }
        $providerModelsDTO = ProviderAdminAssembler::entityToModelsDTO($providerConfigEntity);

        $this->fillProviderInfoAndIcon($providerEntity, $providerModelsDTO);
        return $providerModelsDTO;
    }

    // delete服务商

    /**
     * @throws Exception
     */
    public function deleteProvider(
        DelightfulUserAuthorization $authorization,
        string $id,
    ): void {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // 事务
        Db::beginTransaction();

        try {
            if ($this->isOfficialOrganization($authorization->getOrganizationCode())) {
                $providerModelEntities = $this->providerModelDomainService->getByProviderConfigId($dataIsolation, $id);
                $modelParentIds = array_column($providerModelEntities, 'id');
                $cloneDataIsolation = clone $dataIsolation;
                $cloneDataIsolation->disabled();
                $this->providerModelDomainService->deleteByModelParentIds($cloneDataIsolation, $modelParentIds);
            }

            $this->providerConfigDomainService->delete($dataIsolation, $id);
            $this->providerModelDomainService->deleteByProviderId($dataIsolation, $id);
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    // delete模型

    /**
     * @throws Exception
     */
    public function deleteModel(
        DelightfulUserAuthorization $authorization,
        string $id,
    ): void {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // 获取模型info，用于触发事件
        $modelEntity = $this->providerModelDomainService->getById($dataIsolation, $id);

        Db::beginTransaction();
        try {
            if ($this->isOfficialOrganization($authorization->getOrganizationCode())) {
                $cloneDataIsolation = clone $dataIsolation;
                $cloneDataIsolation->disabled();
                $this->providerModelDomainService->deleteByModelParentId($cloneDataIsolation, $id);
            }
            $this->providerModelDomainService->deleteById($dataIsolation, $id);

            // 触发模型delete事件
            $this->eventDispatcher->dispatch(new ProviderModelDeletedEvent(
                $id,
                $modelEntity->getServiceProviderConfigId(),
                $authorization->getOrganizationCode()
            ));

            Db::commit();
        } catch (Exception $e) {
            $this->logger->error('delete模型fail', ['error' => $e->getMessage()]);
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 修改模型status.
     */
    public function updateModelStatus(
        DelightfulUserAuthorization $authorization,
        string $id,
        int $status,
    ): void {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        $statusEnum = Status::from($status);
        $this->providerModelDomainService->updateStatus($dataIsolation, $id, $statusEnum);
    }

    // save模型
    public function saveModel(DelightfulUserAuthorization $authorization, SaveProviderModelDTO $saveProviderModelDTO): array
    {
        $dataIsolation = ProviderDataIsolation::create($authorization->getOrganizationCode(), $authorization->getId());

        // 记录是create还是更新
        $isCreate = ! $saveProviderModelDTO->getId();

        $saveProviderModelDTO = $this->providerModelDomainService->saveModel($dataIsolation, $saveProviderModelDTO);

        // 获取save后的模型实体
        $modelEntity = $this->providerModelDomainService->getById($dataIsolation, $saveProviderModelDTO->getId());

        // 触发相应的事件
        if ($isCreate) {
            $this->eventDispatcher->dispatch(new ProviderModelCreatedEvent(
                $modelEntity,
                $authorization->getOrganizationCode()
            ));
        } else {
            $this->eventDispatcher->dispatch(new ProviderModelUpdatedEvent(
                $modelEntity,
                $authorization->getOrganizationCode()
            ));
        }

        $saveProviderModelData = $saveProviderModelDTO->toArray();
        // icon传入是 url，return也need是 url，但是save在数据库是 file_key
        // 所以 SaveProviderModelDTO 的 setIcon 做了 url 到 file_key的convert
        $saveProviderModelData['icon'] = $this->getFileUrl($saveProviderModelDTO->getIcon());
        return $saveProviderModelData;
    }

    /**
     * according toorganization编码和服务商category获取活跃的服务商configuration.
     * @param string $organizationCode organization编码
     * @param Category $category 服务商category
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProvidersModelsByCategory(string $organizationCode, Category $category): array
    {
        // call领域层时pass modelTypes parameter，让仓储层completequery和filter
        $serviceProviderModelsDTOs = $this->adminProviderDomainService->getOrganizationProvidersModelsByCategory($organizationCode, $category);

        // handle图标
        $this->processProviderConfigIcons($serviceProviderModelsDTOs);

        return array_values($serviceProviderModelsDTOs);
    }

    /**
     * @throws Exception
     */
    public function connectivityTest(string $serviceProviderConfigId, string $modelVersion, string $modelPrimaryId, DelightfulUserAuthorization $authorization): ConnectResponse
    {
        // build数据隔离object
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // pass领域层method获取完整的模型详情info
        $providerModelEntity = $this->providerModelDomainService->getById(
            $dataIsolation,
            $modelPrimaryId
        );
        // according to服务商type和模型type进行连通性test
        return match ($this->getConnectivityTestType($providerModelEntity->getCategory()->value, $providerModelEntity->getModelType()->value)) {
            NaturalLanguageProcessing::EMBEDDING => $this->embeddingConnectivityTest($modelPrimaryId, $authorization),
            NaturalLanguageProcessing::LLM => $this->llmConnectivityTest($modelPrimaryId, $authorization),
            default => $this->adminProviderDomainService->vlmConnectivityTest($serviceProviderConfigId, $modelVersion, $authorization->getOrganizationCode()),
        };
    }

    /**
     * 获取所有非官方服务商列表，不依赖于organization.
     *
     * @param Category $category 服务商category
     * @param string $organizationCode organization编码
     * @return ProviderConfigModelsDTO[] 非官方服务商列表
     */
    public function getAllNonOfficialProviders(Category $category, string $organizationCode): array
    {
        // 获取所有非官方服务商
        $serviceProviders = $this->adminProviderDomainService->getAllNonOfficialProviders($category);

        if (empty($serviceProviders)) {
            return [];
        }

        // handle图标
        $this->processServiceProviderEntityListIcons($serviceProviders, $organizationCode);

        return $serviceProviders;
    }

    /**
     * 获取所有可用的服务商列表（include官方服务商），不依赖于organization.
     *
     * @param Category $category 服务商category
     * @param string $organizationCode organization编码
     * @return ProviderConfigModelsDTO[] 所有可用服务商列表
     */
    public function getAllAvailableLlmProviders(Category $category, string $organizationCode): array
    {
        // 获取所有服务商（includeOfficial）
        $serviceProviders = $this->adminProviderDomainService->getAllAvailableProviders($category);

        if (empty($serviceProviders)) {
            return [];
        }

        // handle图标
        $this->processServiceProviderEntityListIcons($serviceProviders, $organizationCode);

        return $serviceProviders;
    }

    /**
     * Get be delightful display models and Delightful provider models visible to current organization.
     * @param string $organizationCode Organization code
     * @return ProviderModelDetailDTO[]
     */
    public function getBeDelightfulDisplayModelsForOrganization(string $organizationCode): array
    {
        $models = $this->adminProviderDomainService->getBeDelightfulDisplayModelsForOrganization($organizationCode);

        if (empty($models)) {
            return [];
        }

        // 收集所有图标路径按organization编码group
        $iconsByOrg = [];
        $iconToModelMap = [];

        foreach ($models as $model) {
            $icon = $model->getIcon();
            if (! empty($icon)) {
                $iconOrganizationCode = substr($icon, 0, strpos($icon, '/'));

                if (! isset($iconsByOrg[$iconOrganizationCode])) {
                    $iconsByOrg[$iconOrganizationCode] = [];
                }
                $iconsByOrg[$iconOrganizationCode][] = $icon;

                if (! isset($iconToModelMap[$icon])) {
                    $iconToModelMap[$icon] = [];
                }
                $iconToModelMap[$icon][] = $model;
            }
        }

        // 批量获取图标URL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $iconOrganizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($iconOrganizationCode, array_unique($icons));
            $iconUrlMap[] = $links;
        }
        ! empty($iconUrlMap) && $iconUrlMap = array_merge(...$iconUrlMap);
        // createDTO并setting图标URL
        $modelDTOs = [];
        foreach ($models as $model) {
            $modelDTO = new ProviderModelDetailDTO($model->toArray());

            $icon = $model->getIcon();
            if (! empty($icon) && isset($iconUrlMap[$icon])) {
                $fileLink = $iconUrlMap[$icon];
                if ($fileLink) {
                    $modelDTO->setIcon($fileLink->getUrl());
                }
            }

            $modelDTOs[] = $modelDTO;
        }

        return $modelDTOs;
    }

    /**
     * 获取官方organization下的所有可用模型.
     * @return ProviderModelDetailDTO[]
     */
    public function queriesModels(DelightfulUserAuthorization $authorization, ProviderModelQuery $providerModelQuery): array
    {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );
        $queriesModels = $this->adminProviderDomainService->queriesModels($dataIsolation, $providerModelQuery);
        $providerConfigModelsDTOs = [];
        foreach ($queriesModels as $model) {
            $providerConfigModelsDTOs[] = new ProviderModelDetailDTO($model->toArray());
        }
        $this->processModelIcons($providerConfigModelsDTOs);
        return $providerConfigModelsDTOs;
    }

    /**
     * initializeDelightful服务商configuration数据.
     */
    public function initializeDelightfulProviderConfigs(): int
    {
        return $this->adminProviderDomainService->initializeDelightfulProviderConfigs();
    }

    /**
     * @param $providerModelDetailDTOs ProviderModelDetailDTO[]
     */
    private function processModelIcons(array $providerModelDetailDTOs): void
    {
        if (empty($providerModelDetailDTOs)) {
            return;
        }

        // 收集所有图标路径按organization编码group
        $iconsByOrg = [];
        $iconToModelMap = [];

        foreach ($providerModelDetailDTOs as $model) {
            $icon = $model->getIcon();
            if (empty($icon)) {
                continue;
            }

            $icon = FileAssembler::formatPath($icon);
            $organizationCode = substr($icon, 0, strpos($icon, '/'));

            if (! isset($iconsByOrg[$organizationCode])) {
                $iconsByOrg[$organizationCode] = [];
            }
            $iconsByOrg[$organizationCode][] = $icon;

            if (! isset($iconToModelMap[$icon])) {
                $iconToModelMap[$icon] = [];
            }
            $iconToModelMap[$icon][] = $model;
        }

        // 批量获取图标URL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $organizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));
            $iconUrlMap = array_merge($iconUrlMap, $links);
        }

        // setting图标URL
        foreach ($iconUrlMap as $icon => $fileLink) {
            if (isset($iconToModelMap[$icon])) {
                $url = $fileLink ? $fileLink->getUrl() : '';
                foreach ($iconToModelMap[$icon] as $model) {
                    $model->setIcon($url);
                }
            }
        }
    }

    /**
     * 填充 provider info并handle icon.
     */
    private function fillProviderInfoAndIcon(
        ProviderEntity $provider,
        ProviderConfigModelsDTO $providerModelsDTO
    ): void {
        // 填充 provider 基本info
        $providerModelsDTO->setName($provider->getName());
        $providerModelsDTO->setDescription($provider->getDescription());
        $providerModelsDTO->setServiceProviderId((string) $provider->getId());
        $providerModelsDTO->setCategory($provider->getCategory()->value);
        $providerModelsDTO->setProviderCode($provider->getProviderCode());
        $providerModelsDTO->setProviderType($provider->getProviderType());
        $providerModelsDTO->setIcon($this->getFileUrl($provider->getIcon()));
    }

    private function getFileUrl(string $icon): string
    {
        if (empty($icon)) {
            return '';
        }
        $icon = FileAssembler::formatPath($icon);

        $organizationCode = substr($icon, 0, strpos($icon, '/'));
        $fileLink = $this->fileDomainService->getLink($organizationCode, $icon);
        return $fileLink !== null ? $fileLink->getUrl() : '';
    }

    /**
     * 统一handle Provider 和 Models 的图标，convert为完整URL.
     */
    private function processProviderAndModelsIcons(ProviderConfigModelsDTO $providerDTO): void
    {
        // 收集所有图标路径和对应的organization编码
        $iconsByOrg = [];
        $providerIconMap = [];  // provider图标映射
        $modelIconMap = [];     // 模型图标映射

        // handle provider 图标
        $providerIcon = $providerDTO->getIcon();
        if (! empty($providerIcon)) {
            $providerIcon = FileAssembler::formatPath($providerIcon);
            $organizationCode = substr($providerIcon, 0, strpos($providerIcon, '/'));
            /* @phpstan-ignore-next-line */
            if (! isset($iconsByOrg[$organizationCode])) {
                $iconsByOrg[$organizationCode] = [];
            }
            $iconsByOrg[$organizationCode][] = $providerIcon;
            $providerIconMap[$providerIcon] = $providerDTO;
        }

        // handle模型图标
        $modelEntities = $providerDTO->getModels();
        if (! empty($modelEntities)) {
            foreach ($modelEntities as $modelEntity) {
                $icon = $modelEntity->getIcon();
                if (empty($icon)) {
                    continue;
                }

                $icon = FileAssembler::formatPath($icon);
                $organizationCode = substr($icon, 0, strpos($icon, '/'));

                if (! isset($iconsByOrg[$organizationCode])) {
                    $iconsByOrg[$organizationCode] = [];
                }
                $iconsByOrg[$organizationCode][] = $icon;

                // 记录图标到模型的映射关系
                if (! isset($modelIconMap[$icon])) {
                    $modelIconMap[$icon] = [];
                }
                $modelIconMap[$icon][] = $modelEntity;
            }
        }

        // 按organization编码批量获取图标URL
        foreach ($iconsByOrg as $organizationCode => $icons) {
            $iconUrlMap = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));

            // setting图标URL
            foreach ($iconUrlMap as $icon => $fileLink) {
                $url = $fileLink ? $fileLink->getUrl() : '';

                // setting provider 图标URL
                if (isset($providerIconMap[$icon])) {
                    $providerIconMap[$icon]->setIcon($url);
                }

                // setting模型图标URL
                if (isset($modelIconMap[$icon])) {
                    foreach ($modelIconMap[$icon] as $modelEntity) {
                        $modelEntity->setIcon($url);
                    }
                }
            }
        }
    }

    // 是否是官方organization
    private function isOfficialOrganization(string $organizationCode): bool
    {
        $officialOrganization = config('service_provider.office_organization');
        return $organizationCode === $officialOrganization;
    }

    /**
     * 获取联通testtype.
     */
    private function getConnectivityTestType(string $category, int $modelType): NaturalLanguageProcessing
    {
        if (Category::from($category) === Category::LLM) {
            return $modelType === ModelType::EMBEDDING->value ? NaturalLanguageProcessing::EMBEDDING : NaturalLanguageProcessing::LLM;
        }
        return NaturalLanguageProcessing::DEFAULT;
    }

    private function embeddingConnectivityTest(string $modelPrimaryId, DelightfulUserAuthorization $authorization): ConnectResponse
    {
        $connectResponse = new ConnectResponse();
        $llmAppService = di(LLMAppService::class);
        $proxyModelRequest = new EmbeddingsDTO();
        if (defined('DELIGHTFUL_ACCESS_TOKEN')) {
            $proxyModelRequest->setAccessToken(DELIGHTFUL_ACCESS_TOKEN);
        }
        $proxyModelRequest->setModel($modelPrimaryId);
        $proxyModelRequest->setInput('test');
        $proxyModelRequest->setEnableHighAvailability(false); // 连通性test时不启用高可用
        $proxyModelRequest->setBusinessParams([
            'organization_id' => $authorization->getOrganizationCode(),
            'user_id' => $authorization->getId(),
            'source_id' => 'connectivity_test',
        ]);
        try {
            $llmAppService->embeddings($proxyModelRequest);
        } catch (Exception $exception) {
            $connectResponse->setStatus(false);
            $connectResponse->setMessage($exception->getMessage());
            return $connectResponse;
        }
        $connectResponse->setStatus(true);
        return $connectResponse;
    }

    private function llmConnectivityTest(string $modelPrimaryId, DelightfulUserAuthorization $authorization): ConnectResponse
    {
        $connectResponse = new ConnectResponse();
        $llmAppService = di(LLMAppService::class);
        $completionDTO = new CompletionDTO();
        if (defined('DELIGHTFUL_ACCESS_TOKEN')) {
            $completionDTO->setAccessToken(DELIGHTFUL_ACCESS_TOKEN);
        }
        $completionDTO->setMessages([['role' => 'user', 'content' => '你好']]);
        $completionDTO->setModel($modelPrimaryId);
        $completionDTO->setEnableHighAvailability(false); // 连通性test时不启用高可用
        $completionDTO->setBusinessParams([
            'organization_id' => $authorization->getOrganizationCode(),
            'user_id' => $authorization->getId(),
            'source_id' => 'connectivity_test',
        ]);
        $completionDTO->setMaxTokens(-1);
        /* @var ChatCompletionResponse $response */
        try {
            $llmAppService->chatCompletion($completionDTO);
        } catch (Exception $exception) {
            $connectResponse->setStatus(false);
            $connectResponse->setMessage($exception->getMessage());
            return $connectResponse;
        }
        $connectResponse->setStatus(true);
        return $connectResponse;
    }

    /**
     * handle服务提供商实体列表的图标.
     *
     * @param ProviderConfigModelsDTO[] $serviceProviders 服务提供商实体列表
     * @param string $organizationCode organization编码
     */
    private function processServiceProviderEntityListIcons(array $serviceProviders, string $organizationCode): void
    {
        // 收集所有图标
        $icons = [];
        foreach ($serviceProviders as $serviceProvider) {
            $icons[] = $serviceProvider->getIcon();
        }

        // 批量获取所有图标的链接
        $iconUrlMap = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));

        // 只handle图标URL，直接return实体object
        foreach ($serviceProviders as $serviceProvider) {
            $icon = $serviceProvider->getIcon();

            // 如果有URL映射，use映射的URL
            if (isset($iconUrlMap[$icon])) {
                $serviceProvider->setIcon($iconUrlMap[$icon]->getUrl());
            }
        }
    }

    /**
     * handle服务商configuration图标.
     *
     * @param ProviderConfigDTO[] $providerConfigs 服务商configurationDTO列表
     */
    private function processProviderConfigIcons(array $providerConfigs): void
    {
        if (empty($providerConfigs)) {
            return;
        }

        $iconMappings = [];

        // 收集服务商图标
        foreach ($providerConfigs as $configDTO) {
            $this->collectProviderIcon($configDTO, $iconMappings);
        }

        // 批量handle图标URL
        $this->batchProcessIcons($iconMappings);
    }

    /**
     * 收集服务商图标info.
     */
    private function collectProviderIcon(ProviderConfigDTO|ProviderConfigModelsDTO $provider, array &$iconMappings): void
    {
        $providerIcon = $provider->getIcon();
        if (empty($providerIcon)) {
            return;
        }

        $organizationCode = substr($providerIcon, 0, strpos($providerIcon, '/'));

        if (! isset($iconMappings[$organizationCode])) {
            $iconMappings[$organizationCode] = [
                'icons' => [],
                'providerMap' => [],
                'modelMap' => [],
            ];
        }

        $iconMappings[$organizationCode]['icons'][] = $providerIcon;

        if (! isset($iconMappings[$organizationCode]['providerMap'][$providerIcon])) {
            $iconMappings[$organizationCode]['providerMap'][$providerIcon] = [];
        }
        $iconMappings[$organizationCode]['providerMap'][$providerIcon][] = $provider;
    }

    /**
     * 批量handle图标URL.
     */
    private function batchProcessIcons(array $iconMappings): void
    {
        foreach ($iconMappings as $organizationCode => $mapping) {
            /** @var string $organizationCode */
            /** @var array{icons: string[], providerMap: array<string, array>, modelMap: array<string, array>} $mapping */
            $iconUrlMap = $this->fileDomainService->getLinks($organizationCode, array_unique($mapping['icons']));

            foreach ($iconUrlMap as $icon => $fileLink) {
                /** @var string $icon */
                $url = $fileLink ? $fileLink->getUrl() : '';

                // setting服务商图标URL
                $providerMap = $mapping['providerMap'];
                if (isset($providerMap[$icon])) {
                    $providers = $providerMap[$icon];
                    /** @var ProviderConfigDTO|ProviderConfigModelsDTO $provider */
                    foreach ($providers as $provider) {
                        $provider->setIcon($url);
                    }
                }

                // setting模型图标URL
                $modelMap = $mapping['modelMap'];
                if (isset($modelMap[$icon])) {
                    $models = $modelMap[$icon];
                    /** @var ProviderModelDetailDTO $model */
                    foreach ($models as $model) {
                        $model->setIcon($url);
                    }
                }
            }
        }
    }
}
