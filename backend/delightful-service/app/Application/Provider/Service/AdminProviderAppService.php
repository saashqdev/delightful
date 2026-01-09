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
     * according toservicequotientconfigurationIDgetservicequotientdetailedinfo.
     */
    public function getProviderModelsByConfigId(
        DelightfulUserAuthorization $authorization,
        string $configId
    ): ?ProviderConfigModelsDTO {
        // builddataisolationobject
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // pass领域layermethodonetimepropertygetservicequotient,configurationandmodelinfo
        $providerModels = $this->providerConfigDomainService->getProviderModelsByConfigId($dataIsolation, $configId);
        if ($providerModels === null) {
            return null;
        }

        // ProviderModelsDTO already经contain所haveneeddata,统onehandle provider and models  icon andreturn
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

        // 触hairservicequotientconfigurationupdateevent
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

        // 触hairservicequotientconfigurationcreateevent
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

    // deleteservicequotient

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

        // transaction
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

    // deletemodel

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

        // getmodelinfo,useat触hairevent
        $modelEntity = $this->providerModelDomainService->getById($dataIsolation, $id);

        Db::beginTransaction();
        try {
            if ($this->isOfficialOrganization($authorization->getOrganizationCode())) {
                $cloneDataIsolation = clone $dataIsolation;
                $cloneDataIsolation->disabled();
                $this->providerModelDomainService->deleteByModelParentId($cloneDataIsolation, $id);
            }
            $this->providerModelDomainService->deleteById($dataIsolation, $id);

            // 触hairmodeldeleteevent
            $this->eventDispatcher->dispatch(new ProviderModelDeletedEvent(
                $id,
                $modelEntity->getServiceProviderConfigId(),
                $authorization->getOrganizationCode()
            ));

            Db::commit();
        } catch (Exception $e) {
            $this->logger->error('deletemodelfail', ['error' => $e->getMessage()]);
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * modifymodelstatus.
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

    // savemodel
    public function saveModel(DelightfulUserAuthorization $authorization, SaveProviderModelDTO $saveProviderModelDTO): array
    {
        $dataIsolation = ProviderDataIsolation::create($authorization->getOrganizationCode(), $authorization->getId());

        // recordiscreatealsoisupdate
        $isCreate = ! $saveProviderModelDTO->getId();

        $saveProviderModelDTO = $this->providerModelDomainService->saveModel($dataIsolation, $saveProviderModelDTO);

        // getsavebackmodel实body
        $modelEntity = $this->providerModelDomainService->getById($dataIsolation, $saveProviderModelDTO->getId());

        // 触hair相应event
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
        // icon传入is url,returnalsoneedis url,butissaveindatabaseis file_key
        // 所by SaveProviderModelDTO  setIcon 做 url to file_keyconvert
        $saveProviderModelData['icon'] = $this->getFileUrl($saveProviderModelDTO->getIcon());
        return $saveProviderModelData;
    }

    /**
     * according toorganizationencodingandservicequotientcategorygetactiveservicequotientconfiguration.
     * @param string $organizationCode organizationencoding
     * @param Category $category servicequotientcategory
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProvidersModelsByCategory(string $organizationCode, Category $category): array
    {
        // call领域layero clockpass modelTypes parameter,let仓储layercompletequeryandfilter
        $serviceProviderModelsDTOs = $this->adminProviderDomainService->getOrganizationProvidersModelsByCategory($organizationCode, $category);

        // handlegraph标
        $this->processProviderConfigIcons($serviceProviderModelsDTOs);

        return array_values($serviceProviderModelsDTOs);
    }

    /**
     * @throws Exception
     */
    public function connectivityTest(string $serviceProviderConfigId, string $modelVersion, string $modelPrimaryId, DelightfulUserAuthorization $authorization): ConnectResponse
    {
        // builddataisolationobject
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        // pass领域layermethodgetcompletemodeldetailinfo
        $providerModelEntity = $this->providerModelDomainService->getById(
            $dataIsolation,
            $modelPrimaryId
        );
        // according toservicequotienttypeandmodeltypeconduct连通propertytest
        return match ($this->getConnectivityTestType($providerModelEntity->getCategory()->value, $providerModelEntity->getModelType()->value)) {
            NaturalLanguageProcessing::EMBEDDING => $this->embeddingConnectivityTest($modelPrimaryId, $authorization),
            NaturalLanguageProcessing::LLM => $this->llmConnectivityTest($modelPrimaryId, $authorization),
            default => $this->adminProviderDomainService->vlmConnectivityTest($serviceProviderConfigId, $modelVersion, $authorization->getOrganizationCode()),
        };
    }

    /**
     * get所havenon官方servicequotientcolumntable,notdependencyatorganization.
     *
     * @param Category $category servicequotientcategory
     * @param string $organizationCode organizationencoding
     * @return ProviderConfigModelsDTO[] non官方servicequotientcolumntable
     */
    public function getAllNonOfficialProviders(Category $category, string $organizationCode): array
    {
        // get所havenon官方servicequotient
        $serviceProviders = $this->adminProviderDomainService->getAllNonOfficialProviders($category);

        if (empty($serviceProviders)) {
            return [];
        }

        // handlegraph标
        $this->processServiceProviderEntityListIcons($serviceProviders, $organizationCode);

        return $serviceProviders;
    }

    /**
     * get所havecanuseservicequotientcolumntable(include官方servicequotient),notdependencyatorganization.
     *
     * @param Category $category servicequotientcategory
     * @param string $organizationCode organizationencoding
     * @return ProviderConfigModelsDTO[] 所havecanuseservicequotientcolumntable
     */
    public function getAllAvailableLlmProviders(Category $category, string $organizationCode): array
    {
        // get所haveservicequotient(includeOfficial)
        $serviceProviders = $this->adminProviderDomainService->getAllAvailableProviders($category);

        if (empty($serviceProviders)) {
            return [];
        }

        // handlegraph标
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

        // 收collection所havegraph标path按organizationencodinggroup
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

        // batchquantitygetgraph标URL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $iconOrganizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($iconOrganizationCode, array_unique($icons));
            $iconUrlMap[] = $links;
        }
        ! empty($iconUrlMap) && $iconUrlMap = array_merge(...$iconUrlMap);
        // createDTOandsettinggraph标URL
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
     * get官方organizationdown所havecanusemodel.
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
     * initializeDelightfulservicequotientconfigurationdata.
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

        // 收collection所havegraph标path按organizationencodinggroup
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

        // batchquantitygetgraph标URL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $organizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));
            $iconUrlMap = array_merge($iconUrlMap, $links);
        }

        // settinggraph标URL
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
     * populate provider infoandhandle icon.
     */
    private function fillProviderInfoAndIcon(
        ProviderEntity $provider,
        ProviderConfigModelsDTO $providerModelsDTO
    ): void {
        // populate provider 基本info
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
     * 统onehandle Provider and Models graph标,convertforcompleteURL.
     */
    private function processProviderAndModelsIcons(ProviderConfigModelsDTO $providerDTO): void
    {
        // 收collection所havegraph标pathandto应organizationencoding
        $iconsByOrg = [];
        $providerIconMap = [];  // providergraph标mapping
        $modelIconMap = [];     // modelgraph标mapping

        // handle provider graph标
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

        // handlemodelgraph标
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

                // recordgraph标tomodelmappingclose系
                if (! isset($modelIconMap[$icon])) {
                    $modelIconMap[$icon] = [];
                }
                $modelIconMap[$icon][] = $modelEntity;
            }
        }

        // 按organizationencodingbatchquantitygetgraph标URL
        foreach ($iconsByOrg as $organizationCode => $icons) {
            $iconUrlMap = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));

            // settinggraph标URL
            foreach ($iconUrlMap as $icon => $fileLink) {
                $url = $fileLink ? $fileLink->getUrl() : '';

                // setting provider graph标URL
                if (isset($providerIconMap[$icon])) {
                    $providerIconMap[$icon]->setIcon($url);
                }

                // settingmodelgraph标URL
                if (isset($modelIconMap[$icon])) {
                    foreach ($modelIconMap[$icon] as $modelEntity) {
                        $modelEntity->setIcon($url);
                    }
                }
            }
        }
    }

    // whetheris官方organization
    private function isOfficialOrganization(string $organizationCode): bool
    {
        $officialOrganization = config('service_provider.office_organization');
        return $organizationCode === $officialOrganization;
    }

    /**
     * get联通testtype.
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
        $proxyModelRequest->setEnableHighAvailability(false); // 连通propertytesto clocknotenablehighcanuse
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
        $completionDTO->setMessages([['role' => 'user', 'content' => 'yougood']]);
        $completionDTO->setModel($modelPrimaryId);
        $completionDTO->setEnableHighAvailability(false); // 连通propertytesto clocknotenablehighcanuse
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
     * handleserviceprovidequotient实bodycolumntablegraph标.
     *
     * @param ProviderConfigModelsDTO[] $serviceProviders serviceprovidequotient实bodycolumntable
     * @param string $organizationCode organizationencoding
     */
    private function processServiceProviderEntityListIcons(array $serviceProviders, string $organizationCode): void
    {
        // 收collection所havegraph标
        $icons = [];
        foreach ($serviceProviders as $serviceProvider) {
            $icons[] = $serviceProvider->getIcon();
        }

        // batchquantityget所havegraph标link
        $iconUrlMap = $this->fileDomainService->getLinks($organizationCode, array_unique($icons));

        // onlyhandlegraph标URL,直接return实bodyobject
        foreach ($serviceProviders as $serviceProvider) {
            $icon = $serviceProvider->getIcon();

            // ifhaveURLmapping,usemappingURL
            if (isset($iconUrlMap[$icon])) {
                $serviceProvider->setIcon($iconUrlMap[$icon]->getUrl());
            }
        }
    }

    /**
     * handleservicequotientconfigurationgraph标.
     *
     * @param ProviderConfigDTO[] $providerConfigs servicequotientconfigurationDTOcolumntable
     */
    private function processProviderConfigIcons(array $providerConfigs): void
    {
        if (empty($providerConfigs)) {
            return;
        }

        $iconMappings = [];

        // 收collectionservicequotientgraph标
        foreach ($providerConfigs as $configDTO) {
            $this->collectProviderIcon($configDTO, $iconMappings);
        }

        // batchquantityhandlegraph标URL
        $this->batchProcessIcons($iconMappings);
    }

    /**
     * 收collectionservicequotientgraph标info.
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
     * batchquantityhandlegraph标URL.
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

                // settingservicequotientgraph标URL
                $providerMap = $mapping['providerMap'];
                if (isset($providerMap[$icon])) {
                    $providers = $providerMap[$icon];
                    /** @var ProviderConfigDTO|ProviderConfigModelsDTO $provider */
                    foreach ($providers as $provider) {
                        $provider->setIcon($url);
                    }
                }

                // settingmodelgraph标URL
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
