<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;
use App\Domain\Provider\DTO\ProviderConfigModelsDTO;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\ProviderType;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderConfigQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\ProviderConfigRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderRepositoryInterface;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderAdminAssembler;
use App\Interfaces\Provider\Assembler\ProviderConfigIdAssembler;
use DateTime;

class ProviderConfigDomainService extends AbstractProviderDomainService
{
    public function __construct(
        private readonly ProviderConfigRepositoryInterface $serviceProviderConfigRepository,
        private readonly ProviderModelRepositoryInterface $providerModelRepository,
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly LockerInterface $locker,
    ) {
    }

    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getById($dataIsolation, $id);
    }

    /**
     * 通过 service_provider_config_id 获取服务商、配置和模型的aggregate信息.
     * 支持传入服务商模板 id.
     * @param string $configId 可能是模板 id，比如 ProviderConfigIdAssembler
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigModelsDTO
    {
        // 1. 获取服务商配置实体，contain模板ID和虚拟Delightful服务商的统一handle
        $providerConfigEntity = $this->getProviderConfig($dataIsolation, $configId);
        if (! $providerConfigEntity) {
            return null;
        }
        // 存在模板虚拟的 configId 和已经写入数据库的 configId，因此这里用 getProviderConfig return的服务商 id 替换传入的value
        $configId = (string) $providerConfigEntity->getId();
        // 2. query Provider
        $providerEntity = $this->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());
        if (! $providerEntity) {
            return null;
        }

        // 3. query Provider Models（仓储层只returnProviderModelEntity[]）
        $modelEntities = $this->providerModelRepository->getProviderModelsByConfigId($dataIsolation, $configId, $providerEntity);
        // 4. organizationDTO并return
        return ProviderAdminAssembler::getProviderModelsDTO($providerEntity, $providerConfigEntity, $modelEntities);
    }

    /**
     * @param array<int> $ids
     * @return array<ProviderConfigEntity>
     */
    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array
    {
        return $this->serviceProviderConfigRepository->getByIds($dataIsolation, $ids);
    }

    /**
     * 批量获取服务商实体，通过服务商配置ID映射.
     * @param array<int> $configIds 服务商配置IDarray
     * @return array<int, ProviderEntity> 配置ID到服务商实体的映射
     */
    public function getProviderEntitiesByConfigIds(ProviderDataIsolation $dataIsolation, array $configIds): array
    {
        if (empty($configIds)) {
            return [];
        }

        // 批量获取配置实体（不需要organization编码filter）
        $configEntities = $this->serviceProviderConfigRepository->getByIdsWithoutOrganizationFilter($configIds);
        if (empty($configEntities)) {
            return [];
        }

        // 提取服务商ID
        // $configEntities 现在是以 config_id 为 key 的array
        $providerIds = [];
        foreach ($configEntities as $configId => $config) {
            $providerIds[] = $config->getServiceProviderId();
        }
        $providerIds = array_unique($providerIds);

        // 批量获取服务商实体（不需要organization编码filter）
        $providerEntities = $this->providerRepository->getByIdsWithoutOrganizationFilter($providerIds);
        if (empty($providerEntities)) {
            return [];
        }

        // 建立配置ID到服务商实体的映射
        // 两个array都是以 id 为 key，可以直接访问
        $configToProviderMap = [];
        foreach ($configEntities as $configId => $config) {
            $providerId = $config->getServiceProviderId();
            if (isset($providerEntities[$providerId])) {
                $configToProviderMap[$configId] = $providerEntities[$providerId];
            }
        }

        return $configToProviderMap;
    }

    public function updateProviderConfig(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        $configId = $providerConfigEntity->getId();
        // 1. 检查是否为模板 ID
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            return $this->handleTemplateConfigUpdate($dataIsolation, $providerConfigEntity);
        }
        // 2. 普通配置更新逻辑（原有逻辑）
        return $this->handleNormalConfigUpdate($dataIsolation, $providerConfigEntity);
    }

    public function createProviderConfig(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        $provider = $this->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());
        if ($provider === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        if ($provider->getProviderType() === ProviderType::Official) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::SystemError);
        }

        $providerConfigEntity->setStatus(Status::Enabled);

        return $this->serviceProviderConfigRepository->save($dataIsolation, $providerConfigEntity);
    }

    public function delete(ProviderDataIsolation $dataIsolation, string $id): void
    {
        $this->serviceProviderConfigRepository->delete($dataIsolation, $id);
    }

    // 从 ProviderDomainService merge过来的method
    public function getProviderById(ProviderDataIsolation $dataIsolation, int $id): ?ProviderEntity
    {
        return $this->providerRepository->getById($id);
    }

    /**
     * @param array<int> $ids
     * @return array<ProviderEntity>
     */
    public function getProviderByIds(ProviderDataIsolation $dataIsolation, array $ids): array
    {
        return $this->providerRepository->getByIds($ids);
    }

    /**
     * 根据ID获取配置实体（不按organizationfilter，全局query）.
     *
     * @param int $id 配置ID
     * @return null|ProviderConfigEntity 配置实体
     */
    public function getConfigByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * 根据IDarray获取配置实体列表（不按organizationfilter，全局query）.
     *
     * @param array<int> $ids 配置IDarray
     * @return array<int, ProviderConfigEntity> return以id为key的配置实体array
     */
    public function getConfigByIdsWithoutOrganizationFilter(array $ids): array
    {
        return $this->serviceProviderConfigRepository->getByIdsWithoutOrganizationFilter($ids);
    }

    /**
     * 获取服务商配置实体，统一handle所有情况.
     * - 模板ID（格式：providerCode_category）
     * - 常规数据库配置ID.
     */
    public function getProviderConfig(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigEntity
    {
        // 1. 检查是否为服务商模板ID（格式：providerCode_category）
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            // parse模板ID获取ProviderCode和Category
            $parsed = ProviderConfigIdAssembler::parseProviderTemplate($configId);
            if (! $parsed) {
                return null;
            }

            $providerCode = $parsed['providerCode'];
            $category = $parsed['category'];
            if ($providerCode === ProviderCode::Official && OfficialOrganizationUtil::isOfficialOrganization($dataIsolation->getCurrentOrganizationCode())) {
                // 官方organization不允许使用官方服务商
                return null;
            }
            // 获取对应的服务商实体
            $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
            if (! $providerEntity) {
                return null;
            }

            // 先检查organization下是否已存在对应的配置
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // 如果存在真实配置，return真实配置
                return $existingConfig;
            }

            // 不存在时才构造虚拟的服务商配置实体
            return $this->createVirtualProviderConfig($dataIsolation, $providerEntity, $configId);
        }

        // 2. 常规配置query
        return $this->serviceProviderConfigRepository->getById($dataIsolation, (int) $configId);
    }

    /**
     * @return array{total: int, list: array<ProviderConfigEntity>}
     */
    public function queries(ProviderDataIsolation $providerDataIsolation, ProviderConfigQuery $query, Page $createNoPage): array
    {
        return $this->serviceProviderConfigRepository->queries($providerDataIsolation, $query, $createNoPage);
    }

    /**
     * 获取organization下的所有服务商配置.
     * @return ProviderConfigEntity[]
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array
    {
        return $this->serviceProviderConfigRepository->getAllByOrganization($dataIsolation);
    }

    /**
     * 根据ID获取服务商配置（不filterorganization）.
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * 创建虚拟的服务商配置实体（支持所有服务商type）.
     */
    private function createVirtualProviderConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, string $templateId): ProviderConfigEntity
    {
        $configEntity = new ProviderConfigEntity();

        // 除了 delightful 服务商，默认status都是关闭
        $defaultStatus = $providerEntity->getProviderCode() === ProviderCode::Official
            ? Status::Enabled
            : Status::Disabled;

        $configEntity->setId($templateId);
        $configEntity->setServiceProviderId($providerEntity->getId());
        $configEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $configEntity->setStatus($defaultStatus);
        $configEntity->setAlias('');
        $configEntity->setCreatedAt(new DateTime());
        $configEntity->setUpdatedAt(new DateTime());

        return $configEntity;
    }

    /**
     * handle模板配置更新逻辑.
     */
    private function handleTemplateConfigUpdate(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        $templateConfigId = $providerConfigEntity->getId();

        // 1. parse模板 ID
        $parsed = ProviderConfigIdAssembler::parseProviderTemplate($templateConfigId);
        if (! $parsed) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        $providerCode = $parsed['providerCode'];
        $category = $parsed['category'];

        // 2. validate模板配置不能为null
        $config = $providerConfigEntity->getConfig();
        if ($config === null || $this->isProviderConfigEmpty($config)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // 3. 获取对应的服务商实体
        $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
        if (! $providerEntity) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 4. 使用互斥锁防止并发创建
        $lockName = sprintf(
            'update_template_config_%s_%s_%s',
            $dataIsolation->getCurrentOrganizationCode(),
            $providerCode->value,
            $category->value
        );
        $lockOwner = uniqid('template_config_', true);

        if (! $this->locker->mutexLock($lockName, $lockOwner, 5)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        try {
            // 5. 查找本organization下相同 provider_code 和 category 的配置
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // 6. 存在则更新
                return $this->updateProviderConfigData($dataIsolation, $existingConfig, $providerConfigEntity);
            }

            // 7. 不存在则创建新配置
            return $this->createNewTemplateConfig($dataIsolation, $providerEntity, $providerConfigEntity);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * handle普通配置更新逻辑（原有逻辑）.
     */
    private function handleNormalConfigUpdate(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        // 获取现有的配置实体
        $existingConfigEntity = $this->serviceProviderConfigRepository->getById($dataIsolation, $providerConfigEntity->getId());
        if ($existingConfigEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // 获取对应的 Provider 信息进行业务规则validate
        $provider = $this->getProviderById($dataIsolation, $existingConfigEntity->getServiceProviderId());
        if ($provider === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 支持修改官方服务商
        /*if ($provider->getProviderType() === ProviderType::Official) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::SystemError);
        }*/

        // 使用统一的配置更新逻辑
        return $this->updateProviderConfigData($dataIsolation, $existingConfigEntity, $providerConfigEntity);
    }

    /**
     * 创建新的模板配置.
     */
    private function createNewTemplateConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, ProviderConfigEntity $templateConfigEntity): ProviderConfigEntity
    {
        // 创建新的配置实体
        $newConfigEntity = new ProviderConfigEntity();
        $newConfigEntity->setServiceProviderId($providerEntity->getId());
        $newConfigEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $newConfigEntity->setConfig($templateConfigEntity->getConfig());
        $newConfigEntity->setAlias($templateConfigEntity->getAlias());
        $newConfigEntity->setStatus($templateConfigEntity->getStatus());
        $newConfigEntity->setCreatedAt(new DateTime());
        $newConfigEntity->setUpdatedAt(new DateTime());

        return $this->serviceProviderConfigRepository->save($dataIsolation, $newConfigEntity);
    }

    /**
     * 统一的配置数据更新逻辑.
     * handle脱敏数据merge、字段更新和save操作.
     */
    private function updateProviderConfigData(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $existingConfig, ProviderConfigEntity $newConfigData): ProviderConfigEntity
    {
        // handle脱敏后的配置数据
        $processedConfig = $this->processDesensitizedConfig(
            $newConfigData->getConfig(),
            $existingConfig->getConfig()
        );

        // 更新配置数据
        $existingConfig->setConfig($processedConfig);

        // 更新其他字段（如果有提供）
        if ($newConfigData->getAlias()) {
            $existingConfig->setAlias($newConfigData->getAlias());
        }
        if ($newConfigData->getTranslate()) {
            $existingConfig->setTranslate($newConfigData->getTranslate());
        }

        $existingConfig->setUpdatedAt(new DateTime());
        $existingConfig->setSort($newConfigData->getSort());
        $existingConfig->setStatus($newConfigData->getStatus());
        // save并return
        return $this->serviceProviderConfigRepository->save($dataIsolation, $existingConfig);
    }

    /**
     * 检查ProviderConfigItem配置是否为null（所有字段都是nullvalue）.
     */
    private function isProviderConfigEmpty(ProviderConfigItem $config): bool
    {
        // 检查所有配置字段是否都为null
        return empty($config->getAk())
               && empty($config->getSk())
               && empty($config->getApiKey())
               && empty($config->getUrl())
               && empty($config->getProxyUrl())
               && empty($config->getApiVersion())
               && empty($config->getDeploymentName())
               && empty($config->getRegion());
    }
}
