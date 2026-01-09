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
     * pass service_provider_config_id get服务商、configuration和model的aggregateinfo.
     * 支持传入服务商template id.
     * @param string $configId 可能是template id，such as ProviderConfigIdAssembler
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigModelsDTO
    {
        // 1. get服务商configuration实体，containtemplateID和虚拟Delightful服务商的统一handle
        $providerConfigEntity = $this->getProviderConfig($dataIsolation, $configId);
        if (! $providerConfigEntity) {
            return null;
        }
        // 存在template虚拟的 configId 和已经writedatabase的 configId，因此这里用 getProviderConfig return的服务商 id 替换传入的value
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
     * 批量get服务商实体，pass服务商configurationID映射.
     * @param array<int> $configIds 服务商configurationIDarray
     * @return array<int, ProviderEntity> configurationID到服务商实体的映射
     */
    public function getProviderEntitiesByConfigIds(ProviderDataIsolation $dataIsolation, array $configIds): array
    {
        if (empty($configIds)) {
            return [];
        }

        // 批量getconfiguration实体（不needorganizationencodingfilter）
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

        // 批量get服务商实体（不needorganizationencodingfilter）
        $providerEntities = $this->providerRepository->getByIdsWithoutOrganizationFilter($providerIds);
        if (empty($providerEntities)) {
            return [];
        }

        // 建立configurationID到服务商实体的映射
        // 两个array都是以 id 为 key，can直接access
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
        // 1. check是否为template ID
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            return $this->handleTemplateConfigUpdate($dataIsolation, $providerConfigEntity);
        }
        // 2. 普通configurationupdate逻辑（原有逻辑）
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
     * according toIDgetconfiguration实体（不按organizationfilter，全局query）.
     *
     * @param int $id configurationID
     * @return null|ProviderConfigEntity configuration实体
     */
    public function getConfigByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * according toIDarraygetconfiguration实体列表（不按organizationfilter，全局query）.
     *
     * @param array<int> $ids configurationIDarray
     * @return array<int, ProviderConfigEntity> return以id为key的configuration实体array
     */
    public function getConfigByIdsWithoutOrganizationFilter(array $ids): array
    {
        return $this->serviceProviderConfigRepository->getByIdsWithoutOrganizationFilter($ids);
    }

    /**
     * get服务商configuration实体，统一handle所有情况.
     * - templateID（format：providerCode_category）
     * - 常规databaseconfigurationID.
     */
    public function getProviderConfig(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigEntity
    {
        // 1. check是否为服务商templateID（format：providerCode_category）
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            // parsetemplateIDgetProviderCode和Category
            $parsed = ProviderConfigIdAssembler::parseProviderTemplate($configId);
            if (! $parsed) {
                return null;
            }

            $providerCode = $parsed['providerCode'];
            $category = $parsed['category'];
            if ($providerCode === ProviderCode::Official && OfficialOrganizationUtil::isOfficialOrganization($dataIsolation->getCurrentOrganizationCode())) {
                // 官方organization不allowuse官方服务商
                return null;
            }
            // get对应的服务商实体
            $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
            if (! $providerEntity) {
                return null;
            }

            // 先checkorganization下是否已存在对应的configuration
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // 如果存在真实configuration，return真实configuration
                return $existingConfig;
            }

            // 不存在时才构造虚拟的服务商configuration实体
            return $this->createVirtualProviderConfig($dataIsolation, $providerEntity, $configId);
        }

        // 2. 常规configurationquery
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
     * getorganization下的所有服务商configuration.
     * @return ProviderConfigEntity[]
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array
    {
        return $this->serviceProviderConfigRepository->getAllByOrganization($dataIsolation);
    }

    /**
     * according toIDget服务商configuration（不filterorganization）.
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * create虚拟的服务商configuration实体（支持所有服务商type）.
     */
    private function createVirtualProviderConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, string $templateId): ProviderConfigEntity
    {
        $configEntity = new ProviderConfigEntity();

        // 除了 delightful 服务商，defaultstatus都是close
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
     * handletemplateconfigurationupdate逻辑.
     */
    private function handleTemplateConfigUpdate(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        $templateConfigId = $providerConfigEntity->getId();

        // 1. parsetemplate ID
        $parsed = ProviderConfigIdAssembler::parseProviderTemplate($templateConfigId);
        if (! $parsed) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        $providerCode = $parsed['providerCode'];
        $category = $parsed['category'];

        // 2. validatetemplateconfiguration不能为null
        $config = $providerConfigEntity->getConfig();
        if ($config === null || $this->isProviderConfigEmpty($config)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // 3. get对应的服务商实体
        $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
        if (! $providerEntity) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 4. use互斥lock防止并发create
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
            // 5. 查找本organization下same provider_code 和 category 的configuration
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // 6. 存在则update
                return $this->updateProviderConfigData($dataIsolation, $existingConfig, $providerConfigEntity);
            }

            // 7. 不存在则create新configuration
            return $this->createNewTemplateConfig($dataIsolation, $providerEntity, $providerConfigEntity);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * handle普通configurationupdate逻辑（原有逻辑）.
     */
    private function handleNormalConfigUpdate(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        // get现有的configuration实体
        $existingConfigEntity = $this->serviceProviderConfigRepository->getById($dataIsolation, $providerConfigEntity->getId());
        if ($existingConfigEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // get对应的 Provider info进行业务规则validate
        $provider = $this->getProviderById($dataIsolation, $existingConfigEntity->getServiceProviderId());
        if ($provider === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 支持修改官方服务商
        /*if ($provider->getProviderType() === ProviderType::Official) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::SystemError);
        }*/

        // use统一的configurationupdate逻辑
        return $this->updateProviderConfigData($dataIsolation, $existingConfigEntity, $providerConfigEntity);
    }

    /**
     * createnewtemplateconfiguration.
     */
    private function createNewTemplateConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, ProviderConfigEntity $templateConfigEntity): ProviderConfigEntity
    {
        // createnewconfiguration实体
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
     * 统一的configurationdataupdate逻辑.
     * handle脱敏datamerge、字段update和save操作.
     */
    private function updateProviderConfigData(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $existingConfig, ProviderConfigEntity $newConfigData): ProviderConfigEntity
    {
        // handle脱敏后的configurationdata
        $processedConfig = $this->processDesensitizedConfig(
            $newConfigData->getConfig(),
            $existingConfig->getConfig()
        );

        // updateconfigurationdata
        $existingConfig->setConfig($processedConfig);

        // update其他字段（如果有提供）
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
     * checkProviderConfigItemconfiguration是否为null（所有字段都是nullvalue）.
     */
    private function isProviderConfigEmpty(ProviderConfigItem $config): bool
    {
        // check所有configuration字段是否都为null
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
