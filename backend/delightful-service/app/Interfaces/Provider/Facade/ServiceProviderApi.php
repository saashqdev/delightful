<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Facade;

use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\Application\Provider\DTO\BeDelightfulModelDTO;
use App\Application\Provider\Service\AdminOriginModelAppService;
use App\Application\Provider\Service\AdminProviderAppService;
use app\Application\Provider\Service\ProviderAppService;
use App\Domain\Provider\DTO\ProviderConfigModelsDTO;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Provider\DTO\CreateProviderConfigRequest;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;
use App\Interfaces\Provider\DTO\UpdateProviderConfigRequest;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use JetBrains\PhpStorm\Deprecated;

#[ApiResponse('low_code')]
class ServiceProviderApi extends AbstractApi
{
    #[Inject]
    protected AdminProviderAppService $adminProviderAppService;

    #[Inject]
    protected AdminOriginModelAppService $adminOriginModelAppService;

    #[Inject]
    protected ProviderAppService $providerAppService;

    /**
     * 不需要判断管理员permission。
     * according to分类getservice商list.
     */
    public function getServiceProviders(RequestInterface $request)
    {
        return $this->getProvidersByCategory($request);
    }

    /**
     * 不需要判断管理员permission。
     * according to分类getservice商list.
     */
    public function getOrganizationProvidersByCategory(RequestInterface $request)
    {
        return $this->getProvidersByCategory($request);
    }

    // getservice商和modellist
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getServiceProviderConfigModels(RequestInterface $request, ?string $serviceProviderConfigId = null)
    {
        $serviceProviderConfigId = $serviceProviderConfigId ?? $request->input('service_provider_config_id') ?? '';
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $providerConfigAggregateDTO = $this->adminProviderAppService->getProviderModelsByConfigId($authenticatable, $serviceProviderConfigId);
        // 将新格式数据转换为旧格式以保持向后兼容性
        return $this->convertToLegacyFormat($providerConfigAggregateDTO);
    }

    // updateservice商
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function updateServiceProviderConfig(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $updateProviderConfigRequest = new UpdateProviderConfigRequest($request->all());
        return $this->adminProviderAppService->updateProvider($authenticatable, $updateProviderConfigRequest);
    }

    // 修改modelstatus
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function updateModelStatus(RequestInterface $request, ?string $modelId = null)
    {
        $modelId = $modelId ?? $request->input('model_id') ?? '';
        $authenticatable = $this->getAuthorization();
        $status = $request->input('status', 0);
        /* @var DelightfulUserAuthorization $authenticatable */
        $this->adminProviderAppService->updateModelStatus($authenticatable, $modelId, $status);
    }

    // get当前organization是否是官方organization
    public function isCurrentOrganizationOfficial(): array
    {
        $organizationCode = $this->getAuthorization()->getOrganizationCode();
        return [
            'is_official' => OfficialOrganizationUtil::isOfficialOrganization($organizationCode),
            'official_organization' => OfficialOrganizationUtil::getOfficialOrganizationCode(),
        ];
    }

    // 保存model
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function saveModelToServiceProvider(RequestInterface $request)
    {
        $authenticatable = $this->getAuthorization();
        $saveProviderModelDTO = new SaveProviderModelDTO($request->all());
        return $this->adminProviderAppService->saveModel($authenticatable, $saveProviderModelDTO);
    }

    /**
     * 连通性test.
     * @throws Exception
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function connectivityTest(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $serviceProviderConfigId = $request->input('service_provider_config_id');
        $modelVersion = $request->input('model_version');
        $modelPrimaryId = $request->input('model_id');
        return $this->adminProviderAppService->connectivityTest($serviceProviderConfigId, $modelVersion, $modelPrimaryId, $authenticatable);
    }

    // deletemodel

    /**
     * @throws Exception
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function deleteModel(RequestInterface $request, ?string $modelId = null)
    {
        $modelId = $modelId ?? $request->input('model_id') ?? '';
        $authenticatable = $this->getAuthorization();
        $this->adminProviderAppService->deleteModel($authenticatable, $modelId);
    }

    // get原始modelid
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function listOriginalModels()
    {
        $authenticatable = $this->getAuthorization();
        return $this->adminOriginModelAppService->list($authenticatable);
    }

    // 增加原始modelid
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addOriginalModel(RequestInterface $request)
    {
        $authenticatable = $this->getAuthorization();
        $modelId = $request->input('model_id');
        $this->adminOriginModelAppService->create($authenticatable, $modelId);
    }

    // organization添加service商
    #[Deprecated]
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addServiceProviderForOrganization(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $createProviderConfigRequest = new CreateProviderConfigRequest($request->all());
        return $this->adminProviderAppService->createProvider($authenticatable, $createProviderConfigRequest);
    }

    // deleteservice商
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function deleteServiceProviderForOrganization(RequestInterface $request, ?string $serviceProviderConfigId = null)
    {
        $serviceProviderConfigId = $serviceProviderConfigId ?? $request->input('service_provider_config_id') ?? '';

        $authenticatable = $this->getAuthorization();
        $this->adminProviderAppService->deleteProvider($authenticatable, $serviceProviderConfigId);
    }

    // organization添加model标识
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addModelIdForOrganization(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $modelId = $request->input('model_id');
        $this->adminOriginModelAppService->create($authenticatable, $modelId);
    }

    // organizationdeletemodel标识
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function deleteModelIdForOrganization(RequestInterface $request, ?string $modelId = null)
    {
        $modelId = $modelId ?? $request->input('model_id') ?? '';
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $this->adminOriginModelAppService->delete($authenticatable, $modelId);
    }

    /**
     * get所有非官方LLMservice商list
     * 直接从database中querycategory为llm且provider_type不为OFFICIAL的service商
     * 不依赖于当前organization，适用于需要添加service商的场景.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getNonOfficialLlmProviders()
    {
        $authenticatable = $this->getAuthorization();
        // 直接get所有LLMtype的非官方service商
        return $this->adminProviderAppService->getAllNonOfficialProviders(Category::LLM, $authenticatable->getOrganizationCode());
    }

    /**
     * get所有可用的LLMservice商list（include官方service商）.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getAllAvailableLlmProviders()
    {
        $authenticatable = $this->getAuthorization();
        // get所有LLMtype的service商（includeOfficial）
        return $this->adminProviderAppService->getAllAvailableLlmProviders(Category::LLM, $authenticatable->getOrganizationCode());
    }

    /**
     * Get be delightful display models and Delightful provider models visible to current organization.
     * @return BeDelightfulModelDTO[]
     */
    public function getBeDelightfulDisplayModels(): array
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();

        return $this->providerAppService->getBeDelightfulDisplayModelsForOrganization($authenticatable->getOrganizationCode());
    }

    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function queriesModels(RequestInterface $request): array
    {
        $authenticatable = $this->getAuthorization();
        $providerModelQuery = new ProviderModelQuery($request->all());
        return $this->adminProviderAppService->queriesModels($authenticatable, $providerModelQuery);
    }

    /**
     * according to分类getservice商通用逻辑.
     * @param RequestInterface $request 请求object
     * @return array service商list
     */
    private function getProvidersByCategory(RequestInterface $request): array
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $category = $request->input('category', 'llm');
        $serviceProviderCategory = Category::tryFrom($category);
        if (! $serviceProviderCategory) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::InvalidModelType);
        }

        return $this->adminProviderAppService->getOrganizationProvidersModelsByCategory(
            $authenticatable->getOrganizationCode(),
            $serviceProviderCategory
        );
    }

    /**
     * 将新格式数据转换为旧格式，保持向后兼容性.
     * @param ?ProviderConfigModelsDTO $aggregateDTO 聚合DTOobject
     * @return array 旧格式数据
     */
    private function convertToLegacyFormat(?ProviderConfigModelsDTO $aggregateDTO): array
    {
        if ($aggregateDTO === null) {
            return [];
        }
        $data = $aggregateDTO->toArray();

        // 如果不是新格式结构，直接return
        if (! isset($data['provider_config'])) {
            return $data;
        }

        // 将 provider_config content提升到根级别，并添加 alias 和 models
        return array_merge($data['provider_config'], [
            'alias' => $data['provider_config']['translate']['alias']['zh_CN'] ?? '',
            'models' => $data['models'] ?? [],
        ]);
    }
}
