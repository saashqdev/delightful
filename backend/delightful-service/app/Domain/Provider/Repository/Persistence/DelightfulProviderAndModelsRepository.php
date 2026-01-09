<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\DTO\Item\ModelConfigItem;
use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\DisabledByType;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\DelightfulProviderAndModelsInterface;
use App\Domain\Provider\Repository\Persistence\Model\ProviderConfigModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelModel;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderConfigAssembler;
use App\Interfaces\Provider\Assembler\ProviderConfigIdAssembler;
use App\Interfaces\Provider\Assembler\ProviderModelAssembler;
use DateTime;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class DelightfulProviderAndModelsRepository extends AbstractProviderModelRepository implements DelightfulProviderAndModelsInterface
{
    protected bool $filterOrganizationCode = true;

    public function __construct(
        private readonly PackageFilterInterface $packageFilter,
        private readonly ProviderRepository $providerRepository,
        private readonly LockerInterface $locker
    ) {
    }

    /**
     * getorganizationdown的 Delightful service商configuration（not containmodeldetail）.
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // 1. 判断organizationencodingwhether是官方organization，if是，thenreturn null
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return null;
        }

        // 2. 先query ProviderCode::Official 的service商 ID
        $delightfulProvider = $this->providerRepository->getOfficial($category);
        if (! $delightfulProvider) {
            return null;
        }

        // 3. querycurrentorganizationwhether已have该service商的configuration
        $configBuilder = $this->createConfigQuery()->where('organization_code', $organizationCode);
        $configBuilder->where('service_provider_id', $delightfulProvider->getId());

        // iffinger定了status，添加statusfilter
        if ($status !== null) {
            $configBuilder->where('status', $status->value);
        }

        $configResult = Db::select($configBuilder->toSql(), $configBuilder->getBindings());

        // if找to现haveconfiguration，直接return
        if (! empty($configResult)) {
            // 批quantityquery对应的 provider info
            $providerMap = [$delightfulProvider->getId() => $delightfulProvider->toArray()];
            return ProviderConfigAssembler::toDTOWithProvider($configResult[0], $providerMap);
        }

        // 4. nothave找toconfiguration，buildtemplatedata的 ProviderConfigDTO
        // iffinger定了statusandnot是enablestatus，thennotreturntemplatedata
        if ($status !== null && $status !== Status::Enabled) {
            return null;
        }

        // according toCategorytypesetting对应的organizationDelightfulservice商templateconfigurationID
        $templateId = ProviderConfigIdAssembler::generateProviderTemplate(ProviderCode::Official, $category);

        $templateData = [
            'id' => $templateId,
            'service_provider_id' => $delightfulProvider->getId(),
            'organization_code' => $organizationCode,
            'config' => [],
            'decryptedConfig' => [],
            'status' => Status::Enabled->value,
            'alias' => '',
            'translate' => $delightfulProvider->getTranslate(),
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'name' => $delightfulProvider->getName(),
            'description' => $delightfulProvider->getDescription(),
            'icon' => $delightfulProvider->getIcon(),
            'provider_type' => $delightfulProvider->getProviderType()->value,
            'category' => $category->value,
            'provider_code' => $delightfulProvider->getProviderCode()->value,
            'remark' => '',
        ];

        return new ProviderConfigDTO($templateData);
    }

    /**
     * according toorganizationencoding和category别get Delightful service商enablemiddle的modelcolumn表.
     *
     * @param string $organizationCode organizationencoding
     * @param null|Category $category service商category别，为nullo clockreturn所havecategorymodel
     * @return array<ProviderModelEntity> Delightful service商model实bodyarray
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array
    {
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return [];
        }
        // data集 A：get官方organizationdown所haveenable的model（containconfigurationfilter）
        $officialModels = $this->getOfficialEnabledModels($category);

        // ifnothave官方model，直接returnnullarray
        if (empty($officialModels)) {
            return [];
        }

        // 提取官方model的IDarray
        $officialModelIds = [];
        foreach ($officialModels as $officialModel) {
            $officialModelIds[] = $officialModel->getId();
        }

        // data集 B：querycurrentorganizationdown model_parent_id in官方model ID column表middle的model
        $configBuilder = $this->createProviderModelQuery();
        $configBuilder->where('organization_code', $organizationCode)->whereIn('model_parent_id', $officialModelIds);

        // iffinger定了category，添加categoryfiltercondition
        if ($category !== null) {
            $configBuilder->where('category', $category->value);
        }

        $configResult = Db::select($configBuilder->toSql(), $configBuilder->getBindings());
        $modelEntities = ProviderModelAssembler::toEntities($configResult);

        // createconfigurationmodel的mapping表，by model_parent_id 为 key
        $modelMap = [];
        foreach ($modelEntities as $modelEntity) {
            if ($modelEntity->getModelParentId()) {
                $modelMap[$modelEntity->getModelParentId()] = $modelEntity;
            }
        }

        // ifconfigurationmodelmapping为null，直接return官方modelcolumn表
        if (empty($modelMap)) {
            $finalModels = $officialModels;
        } else {
            // handle官方model的statusmerge
            $finalModels = [];
            foreach ($officialModels as $officialModel) {
                $modelId = $officialModel->getId();

                // checkwhetherhave普通organization的quotemodel
                if (isset($modelMap[$modelId])) {
                    $organizationModel = $modelMap[$modelId];

                    // 直接useconfigurationmodel的status替换官方model的status
                    $officialModel->setStatus($organizationModel->getStatus());
                }
                $finalModels[] = $officialModel;
            }
        }

        // applicationset餐filter
        return $this->applyPackageFilteringToModels($finalModels, $organizationCode);
    }

    /**
     * 查找 Delightful modelwhether已经inorganizationmiddle.
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity
    {
        $query = $this->createProviderModelQuery()
            ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
            ->where('model_parent_id', $modelParentId);
        $models = Db::select($query->toSql(), $query->getBindings());
        if (isset($models[0])) {
            return ProviderModelAssembler::toEntity($models[0]);
        }
        return null;
    }

    /**
     * according toIDgetorganization Delightful model.
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity
    {
        $officeOrganization = OfficialOrganizationUtil::getOfficialOrganizationCode();

        $query = $this->createProviderModelQuery();

        $query->where('id', $id)
            ->where('organization_code', $officeOrganization);

        $result = Db::select($query->toSql(), $query->getBindings());
        if (empty($result)) {
            return null;
        }

        return ProviderModelAssembler::toEntity($result[0]);
    }

    /**
     * non官方organizationupdate Delightful modelstatus（写o clock复制逻辑）.
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string {
        // buildlockname和所have者标识
        $lockName = sprintf(
            'copy_delightful_model_%s_%s',
            $dataIsolation->getCurrentOrganizationCode(),
            $officialModel->getId()
        );
        $lockOwner = uniqid('copy_model_', true);

        // get互斥lock，防止并haircreatesame的model
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelOperationLocked);
        }

        try {
            // 1. check官方modelwhetherbe官方disable
            if ($this->isOfficiallyDisabled($officialModel)) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ModelOfficiallyDisabled);
            }

            // 2. 查找现have的organizationmodelrecord（inlock保护downagaintimecheck）
            $organizationModel = $this->getDelightfulModelByParentId($dataIsolation, (string) $officialModel->getId());

            if ($organizationModel) {
                $organizationModelId = (string) $organizationModel->getId();
            } else {
                // 3. createneworganizationmodelrecord
                $newOrganizationModel = $this->copyOfficeModelToOrganization($dataIsolation, $officialModel);
                $organizationModelId = (string) $newOrganizationModel->getId();
            }

            return $organizationModelId;
        } finally {
            // ensure释放lock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * get官方organizationdown所haveenable的model（containconfigurationfilter）.
     *
     * @param null|Category $category service商category别，为nullo clockreturn所havecategorymodel
     * @return array<ProviderModelEntity> filterback的官方modelcolumn表
     */
    private function getOfficialEnabledModels(?Category $category = null): array
    {
        // get官方organizationencoding
        $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

        // 1. 先query官方organizationdownenable的service商configurationID
        $enabledConfigQuery = $this->createConfigQuery()
            ->where('organization_code', $officialOrganizationCode)
            ->where('status', Status::Enabled->value)
            ->select('id');
        $enabledConfigIds = Db::select($enabledConfigQuery->toSql(), $enabledConfigQuery->getBindings());
        $enabledConfigIdArray = array_column($enabledConfigIds, 'id');

        // 2. useenable的configurationIDquery官方organization的enablemodel
        if (! empty($enabledConfigIdArray)) {
            $officialBuilder = $this->createProviderModelQuery()
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereIn('service_provider_config_id', $enabledConfigIdArray);

            // iffinger定了category，添加categoryfiltercondition
            if ($category !== null) {
                $officialBuilder->where('category', $category->value);
            }

            $officialResult = Db::select($officialBuilder->toSql(), $officialBuilder->getBindings());
            return ProviderModelAssembler::toEntities($officialResult);
        }

        return [];
    }

    /**
     * applicationset餐filterhandle（针对model实bodycolumn表）.
     *
     * @param array<ProviderModelEntity> $models model实bodycolumn表
     * @param string $organizationCode organizationencoding
     * @return array<ProviderModelEntity> filterback的model实bodycolumn表
     */
    private function applyPackageFilteringToModels(array $models, string $organizationCode): array
    {
        // if是官方organization，直接return所have
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return $models;
        }
        $currentPackage = $this->packageFilter->getCurrentPackage($organizationCode);
        $filteredModels = [];
        foreach ($models as $model) {
            $visiblePackages = $model->getVisiblePackages();
            // ifnothaveconfiguration可见set餐，then对所haveset餐可见
            if (empty($visiblePackages)) {
                $filteredModels[] = $model;
                continue;
            }

            // ifconfiguration了可见set餐，checkcurrentset餐whetherin其middle
            if ($currentPackage && in_array($currentPackage, $visiblePackages)) {
                $filteredModels[] = $model;
            }
        }

        return $filteredModels;
    }

    /**
     * check官方modelwhetherbe官方disable.
     */
    private function isOfficiallyDisabled(ProviderModelEntity $officialModel): bool
    {
        return $officialModel->getDisabledBy() === DisabledByType::OFFICIAL;
    }

    /**
     * 准备移except软删相关feature，temporary这样写。create带have软deletefilter的 ProviderConfigModel querybuild器.
     */
    private function createConfigQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderConfigModel::query()->whereNull('deleted_at');
    }

    /**
     * 准备移except软删相关feature，temporary这样写。create带have软deletefilter的 ProviderModelModel querybuild器.
     */
    private function createProviderModelQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModelModel::query()->whereNull('deleted_at');
    }

    /**
     * 把官方organization的modelwhen做 Delightful Model writenon官方organization。
     */
    private function copyOfficeModelToOrganization(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): ProviderModelEntity {
        // create新modelrecord(避免新增field导致复制报错，直接allquantity copy 然back set 新value)
        $organizationModel = new ProviderModelEntity($officialModel->toArray());
        $organizationModel->setServiceProviderConfigId(0);
        $organizationModel->setModelParentId($officialModel->getId());
        $organizationModel->setIsOffice(true); // Delightfulservice商down的model
        $organizationModel->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $organizationModel->setId(IdGenerator::getSnowId());
        // 避免error复制 config
        $organizationModel->setConfig(new ModelConfigItem());
        return $this->create($dataIsolation, $organizationModel);
    }
}
