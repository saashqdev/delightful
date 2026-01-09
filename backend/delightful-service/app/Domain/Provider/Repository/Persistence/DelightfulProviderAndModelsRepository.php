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
     * 获取organization下的 Delightful 服务商配置（不含模型详情）.
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // 1. 判断organization编码是否是官方organization，如果是，则return null
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return null;
        }

        // 2. 先query ProviderCode::Official 的服务商 ID
        $delightfulProvider = $this->providerRepository->getOfficial($category);
        if (! $delightfulProvider) {
            return null;
        }

        // 3. query当前organization是否已有该服务商的配置
        $configBuilder = $this->createConfigQuery()->where('organization_code', $organizationCode);
        $configBuilder->where('service_provider_id', $delightfulProvider->getId());

        // 如果指定了status，添加statusfilter
        if ($status !== null) {
            $configBuilder->where('status', $status->value);
        }

        $configResult = Db::select($configBuilder->toSql(), $configBuilder->getBindings());

        // 如果找到现有配置，直接return
        if (! empty($configResult)) {
            // 批量query对应的 provider 信息
            $providerMap = [$delightfulProvider->getId() => $delightfulProvider->toArray()];
            return ProviderConfigAssembler::toDTOWithProvider($configResult[0], $providerMap);
        }

        // 4. 没有找到配置，build模板数据的 ProviderConfigDTO
        // 如果指定了status且不是启用status，则不return模板数据
        if ($status !== null && $status !== Status::Enabled) {
            return null;
        }

        // according toCategorytype设置对应的organizationDelightful服务商模板配置ID
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
     * according toorganization编码和类别获取 Delightful 服务商启用中的模型列表.
     *
     * @param string $organizationCode organization编码
     * @param null|Category $category 服务商类别，为null时return所有分类模型
     * @return array<ProviderModelEntity> Delightful 服务商模型实体array
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array
    {
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return [];
        }
        // 数据集 A：获取官方organization下所有启用的模型（contain配置filter）
        $officialModels = $this->getOfficialEnabledModels($category);

        // 如果没有官方模型，直接returnnullarray
        if (empty($officialModels)) {
            return [];
        }

        // 提取官方模型的IDarray
        $officialModelIds = [];
        foreach ($officialModels as $officialModel) {
            $officialModelIds[] = $officialModel->getId();
        }

        // 数据集 B：query当前organization下 model_parent_id 在官方模型 ID 列表中的模型
        $configBuilder = $this->createProviderModelQuery();
        $configBuilder->where('organization_code', $organizationCode)->whereIn('model_parent_id', $officialModelIds);

        // 如果指定了分类，添加分类filtercondition
        if ($category !== null) {
            $configBuilder->where('category', $category->value);
        }

        $configResult = Db::select($configBuilder->toSql(), $configBuilder->getBindings());
        $modelEntities = ProviderModelAssembler::toEntities($configResult);

        // create配置模型的映射表，以 model_parent_id 为 key
        $modelMap = [];
        foreach ($modelEntities as $modelEntity) {
            if ($modelEntity->getModelParentId()) {
                $modelMap[$modelEntity->getModelParentId()] = $modelEntity;
            }
        }

        // 如果配置模型映射为null，直接return官方模型列表
        if (empty($modelMap)) {
            $finalModels = $officialModels;
        } else {
            // handle官方模型的statusmerge
            $finalModels = [];
            foreach ($officialModels as $officialModel) {
                $modelId = $officialModel->getId();

                // 检查是否有普通organization的引用模型
                if (isset($modelMap[$modelId])) {
                    $organizationModel = $modelMap[$modelId];

                    // 直接用配置模型的status替换官方模型的status
                    $officialModel->setStatus($organizationModel->getStatus());
                }
                $finalModels[] = $officialModel;
            }
        }

        // application套餐filter
        return $this->applyPackageFilteringToModels($finalModels, $organizationCode);
    }

    /**
     * 查找 Delightful 模型是否已经在organization中.
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
     * according toID获取organization Delightful 模型.
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
     * 非官方organization更新 Delightful 模型status（写时复制逻辑）.
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string {
        // build锁名称和所有者标识
        $lockName = sprintf(
            'copy_delightful_model_%s_%s',
            $dataIsolation->getCurrentOrganizationCode(),
            $officialModel->getId()
        );
        $lockOwner = uniqid('copy_model_', true);

        // 获取互斥锁，防止并发create相同的模型
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelOperationLocked);
        }

        try {
            // 1. 检查官方模型是否被官方禁用
            if ($this->isOfficiallyDisabled($officialModel)) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ModelOfficiallyDisabled);
            }

            // 2. 查找现有的organization模型记录（在锁保护下再次检查）
            $organizationModel = $this->getDelightfulModelByParentId($dataIsolation, (string) $officialModel->getId());

            if ($organizationModel) {
                $organizationModelId = (string) $organizationModel->getId();
            } else {
                // 3. create新的organization模型记录
                $newOrganizationModel = $this->copyOfficeModelToOrganization($dataIsolation, $officialModel);
                $organizationModelId = (string) $newOrganizationModel->getId();
            }

            return $organizationModelId;
        } finally {
            // 确保释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * 获取官方organization下所有启用的模型（contain配置filter）.
     *
     * @param null|Category $category 服务商类别，为null时return所有分类模型
     * @return array<ProviderModelEntity> filter后的官方模型列表
     */
    private function getOfficialEnabledModels(?Category $category = null): array
    {
        // 获取官方organization编码
        $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

        // 1. 先query官方organization下启用的服务商配置ID
        $enabledConfigQuery = $this->createConfigQuery()
            ->where('organization_code', $officialOrganizationCode)
            ->where('status', Status::Enabled->value)
            ->select('id');
        $enabledConfigIds = Db::select($enabledConfigQuery->toSql(), $enabledConfigQuery->getBindings());
        $enabledConfigIdArray = array_column($enabledConfigIds, 'id');

        // 2. use启用的配置IDquery官方organization的启用模型
        if (! empty($enabledConfigIdArray)) {
            $officialBuilder = $this->createProviderModelQuery()
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereIn('service_provider_config_id', $enabledConfigIdArray);

            // 如果指定了分类，添加分类filtercondition
            if ($category !== null) {
                $officialBuilder->where('category', $category->value);
            }

            $officialResult = Db::select($officialBuilder->toSql(), $officialBuilder->getBindings());
            return ProviderModelAssembler::toEntities($officialResult);
        }

        return [];
    }

    /**
     * application套餐filterhandle（针对模型实体列表）.
     *
     * @param array<ProviderModelEntity> $models 模型实体列表
     * @param string $organizationCode organization编码
     * @return array<ProviderModelEntity> filter后的模型实体列表
     */
    private function applyPackageFilteringToModels(array $models, string $organizationCode): array
    {
        // 如果是官方organization，直接return所有
        if (OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            return $models;
        }
        $currentPackage = $this->packageFilter->getCurrentPackage($organizationCode);
        $filteredModels = [];
        foreach ($models as $model) {
            $visiblePackages = $model->getVisiblePackages();
            // 如果没有配置可见套餐，则对所有套餐可见
            if (empty($visiblePackages)) {
                $filteredModels[] = $model;
                continue;
            }

            // 如果配置了可见套餐，检查当前套餐是否在其中
            if ($currentPackage && in_array($currentPackage, $visiblePackages)) {
                $filteredModels[] = $model;
            }
        }

        return $filteredModels;
    }

    /**
     * 检查官方模型是否被官方禁用.
     */
    private function isOfficiallyDisabled(ProviderModelEntity $officialModel): bool
    {
        return $officialModel->getDisabledBy() === DisabledByType::OFFICIAL;
    }

    /**
     * 准备移除软删相关功能，临时这样写。create带有软删除filter的 ProviderConfigModel querybuild器.
     */
    private function createConfigQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderConfigModel::query()->whereNull('deleted_at');
    }

    /**
     * 准备移除软删相关功能，临时这样写。create带有软删除filter的 ProviderModelModel querybuild器.
     */
    private function createProviderModelQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModelModel::query()->whereNull('deleted_at');
    }

    /**
     * 把官方organization的模型当做 Delightful Model 写入非官方organization。
     */
    private function copyOfficeModelToOrganization(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): ProviderModelEntity {
        // create新模型记录(避免新增字段导致复制报错，直接全量 copy 然后 set 新value)
        $organizationModel = new ProviderModelEntity($officialModel->toArray());
        $organizationModel->setServiceProviderConfigId(0);
        $organizationModel->setModelParentId($officialModel->getId());
        $organizationModel->setIsOffice(true); // Delightful服务商下的模型
        $organizationModel->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $organizationModel->setId(IdGenerator::getSnowId());
        // 避免error复制 config
        $organizationModel->setConfig(new ModelConfigItem());
        return $this->create($dataIsolation, $organizationModel);
    }
}
