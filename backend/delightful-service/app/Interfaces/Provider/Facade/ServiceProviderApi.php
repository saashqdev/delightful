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
     * notneedjudgeadministratorpermission.
     * according tocategorygetservicequotientlist.
     */
    public function getServiceProviders(RequestInterface $request)
    {
        return $this->getProvidersByCategory($request);
    }

    /**
     * notneedjudgeadministratorpermission.
     * according tocategorygetservicequotientlist.
     */
    public function getOrganizationProvidersByCategory(RequestInterface $request)
    {
        return $this->getProvidersByCategory($request);
    }

    // getservicequotientandmodellist
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getServiceProviderConfigModels(RequestInterface $request, ?string $serviceProviderConfigId = null)
    {
        $serviceProviderConfigId = $serviceProviderConfigId ?? $request->input('service_provider_config_id') ?? '';
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $providerConfigAggregateDTO = $this->adminProviderAppService->getProviderModelsByConfigId($authenticatable, $serviceProviderConfigId);
        // willnewformatdataconvertforoldformatbymaintaintobackcompatibleproperty
        return $this->convertToLegacyFormat($providerConfigAggregateDTO);
    }

    // updateservicequotient
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function updateServiceProviderConfig(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $updateProviderConfigRequest = new UpdateProviderConfigRequest($request->all());
        return $this->adminProviderAppService->updateProvider($authenticatable, $updateProviderConfigRequest);
    }

    // modifymodelstatus
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function updateModelStatus(RequestInterface $request, ?string $modelId = null)
    {
        $modelId = $modelId ?? $request->input('model_id') ?? '';
        $authenticatable = $this->getAuthorization();
        $status = $request->input('status', 0);
        /* @var DelightfulUserAuthorization $authenticatable */
        $this->adminProviderAppService->updateModelStatus($authenticatable, $modelId, $status);
    }

    // getcurrentorganizationwhetheris官方organization
    public function isCurrentOrganizationOfficial(): array
    {
        $organizationCode = $this->getAuthorization()->getOrganizationCode();
        return [
            'is_official' => OfficialOrganizationUtil::isOfficialOrganization($organizationCode),
            'official_organization' => OfficialOrganizationUtil::getOfficialOrganizationCode(),
        ];
    }

    // savemodel
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function saveModelToServiceProvider(RequestInterface $request)
    {
        $authenticatable = $this->getAuthorization();
        $saveProviderModelDTO = new SaveProviderModelDTO($request->all());
        return $this->adminProviderAppService->saveModel($authenticatable, $saveProviderModelDTO);
    }

    /**
     * 连通propertytest.
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

    // getoriginalmodelid
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function listOriginalModels()
    {
        $authenticatable = $this->getAuthorization();
        return $this->adminOriginModelAppService->list($authenticatable);
    }

    // increaseoriginalmodelid
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addOriginalModel(RequestInterface $request)
    {
        $authenticatable = $this->getAuthorization();
        $modelId = $request->input('model_id');
        $this->adminOriginModelAppService->create($authenticatable, $modelId);
    }

    // organizationaddservicequotient
    #[Deprecated]
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addServiceProviderForOrganization(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $createProviderConfigRequest = new CreateProviderConfigRequest($request->all());
        return $this->adminProviderAppService->createProvider($authenticatable, $createProviderConfigRequest);
    }

    // deleteservicequotient
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function deleteServiceProviderForOrganization(RequestInterface $request, ?string $serviceProviderConfigId = null)
    {
        $serviceProviderConfigId = $serviceProviderConfigId ?? $request->input('service_provider_config_id') ?? '';

        $authenticatable = $this->getAuthorization();
        $this->adminProviderAppService->deleteProvider($authenticatable, $serviceProviderConfigId);
    }

    // organizationaddmodelidentifier
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function addModelIdForOrganization(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $modelId = $request->input('model_id');
        $this->adminOriginModelAppService->create($authenticatable, $modelId);
    }

    // organizationdeletemodelidentifier
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::EDIT)]
    public function deleteModelIdForOrganization(RequestInterface $request, ?string $modelId = null)
    {
        $modelId = $modelId ?? $request->input('model_id') ?? '';
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $this->adminOriginModelAppService->delete($authenticatable, $modelId);
    }

    /**
     * get所havenon官方LLMservicequotientlist
     * 直接fromdatabasemiddlequerycategoryforllmandprovider_typenotforOFFICIALservicequotient
     * notdependencyatcurrentorganization,适useatneedaddservicequotient场景.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getNonOfficialLlmProviders()
    {
        $authenticatable = $this->getAuthorization();
        // 直接get所haveLLMtypenon官方servicequotient
        return $this->adminProviderAppService->getAllNonOfficialProviders(Category::LLM, $authenticatable->getOrganizationCode());
    }

    /**
     * get所havecanuseLLMservicequotientlist(include官方servicequotient).
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODEL, DelightfulResourceEnum::ADMIN_AI_IMAGE], DelightfulOperationEnum::QUERY)]
    public function getAllAvailableLlmProviders()
    {
        $authenticatable = $this->getAuthorization();
        // get所haveLLMtypeservicequotient(includeOfficial)
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
     * according tocategorygetservicequotient通uselogic.
     * @param RequestInterface $request requestobject
     * @return array servicequotientlist
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
     * willnewformatdataconvertforoldformat,maintaintobackcompatibleproperty.
     * @param ?ProviderConfigModelsDTO $aggregateDTO aggregateDTOobject
     * @return array oldformatdata
     */
    private function convertToLegacyFormat(?ProviderConfigModelsDTO $aggregateDTO): array
    {
        if ($aggregateDTO === null) {
            return [];
        }
        $data = $aggregateDTO->toArray();

        // ifnotisnewformatstructure,直接return
        if (! isset($data['provider_config'])) {
            return $data;
        }

        // will provider_config contentenhancetorootlevel别,andadd alias and models
        return array_merge($data['provider_config'], [
            'alias' => $data['provider_config']['translate']['alias']['zh_CN'] ?? '',
            'models' => $data['models'] ?? [],
        ]);
    }
}
