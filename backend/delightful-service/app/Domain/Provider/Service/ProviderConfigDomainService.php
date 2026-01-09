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
     * pass service_provider_config_id getservicequotient,configurationandmodelaggregateinfo.
     * support传入servicequotienttemplate id.
     * @param string $configId maybeistemplate id,such as ProviderConfigIdAssembler
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigModelsDTO
    {
        // 1. getservicequotientconfiguration实body,containtemplateIDandvirtualDelightfulservicequotient统onehandle
        $providerConfigEntity = $this->getProviderConfig($dataIsolation, $configId);
        if (! $providerConfigEntity) {
            return null;
        }
        // 存intemplatevirtual configId andalready经writedatabase configId,thereforethiswithinuse getProviderConfig returnservicequotient id replace传入value
        $configId = (string) $providerConfigEntity->getId();
        // 2. query Provider
        $providerEntity = $this->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());
        if (! $providerEntity) {
            return null;
        }

        // 3. query Provider Models(仓储layeronlyreturnProviderModelEntity[])
        $modelEntities = $this->providerModelRepository->getProviderModelsByConfigId($dataIsolation, $configId, $providerEntity);
        // 4. organizationDTOandreturn
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
     * batchquantitygetservicequotient实body,passservicequotientconfigurationIDmapping.
     * @param array<int> $configIds servicequotientconfigurationIDarray
     * @return array<int, ProviderEntity> configurationIDtoservicequotient实bodymapping
     */
    public function getProviderEntitiesByConfigIds(ProviderDataIsolation $dataIsolation, array $configIds): array
    {
        if (empty($configIds)) {
            return [];
        }

        // batchquantitygetconfiguration实body(notneedorganizationencodingfilter)
        $configEntities = $this->serviceProviderConfigRepository->getByIdsWithoutOrganizationFilter($configIds);
        if (empty($configEntities)) {
            return [];
        }

        // extractservicequotientID
        // $configEntities 现inisby config_id for key array
        $providerIds = [];
        foreach ($configEntities as $configId => $config) {
            $providerIds[] = $config->getServiceProviderId();
        }
        $providerIds = array_unique($providerIds);

        // batchquantitygetservicequotient实body(notneedorganizationencodingfilter)
        $providerEntities = $this->providerRepository->getByIdsWithoutOrganizationFilter($providerIds);
        if (empty($providerEntities)) {
            return [];
        }

        // establishconfigurationIDtoservicequotient实bodymapping
        // 两arrayallisby id for key,can直接access
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
        // 1. checkwhetherfortemplate ID
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            return $this->handleTemplateConfigUpdate($dataIsolation, $providerConfigEntity);
        }
        // 2. 普通configurationupdatelogic(原havelogic)
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

    // from ProviderDomainService mergepasscomemethod
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
     * according toIDgetconfiguration实body(not按organizationfilter,all局query).
     *
     * @param int $id configurationID
     * @return null|ProviderConfigEntity configuration实body
     */
    public function getConfigByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * according toIDarraygetconfiguration实bodycolumn表(not按organizationfilter,all局query).
     *
     * @param array<int> $ids configurationIDarray
     * @return array<int, ProviderConfigEntity> returnbyidforkeyconfiguration实bodyarray
     */
    public function getConfigByIdsWithoutOrganizationFilter(array $ids): array
    {
        return $this->serviceProviderConfigRepository->getByIdsWithoutOrganizationFilter($ids);
    }

    /**
     * getservicequotientconfiguration实body,统onehandle所have情况.
     * - templateID(format:providerCode_category)
     * - 常规databaseconfigurationID.
     */
    public function getProviderConfig(ProviderDataIsolation $dataIsolation, string $configId): ?ProviderConfigEntity
    {
        // 1. checkwhetherforservicequotienttemplateID(format:providerCode_category)
        if (ProviderConfigIdAssembler::isAnyProviderTemplate($configId)) {
            // parsetemplateIDgetProviderCodeandCategory
            $parsed = ProviderConfigIdAssembler::parseProviderTemplate($configId);
            if (! $parsed) {
                return null;
            }

            $providerCode = $parsed['providerCode'];
            $category = $parsed['category'];
            if ($providerCode === ProviderCode::Official && OfficialOrganizationUtil::isOfficialOrganization($dataIsolation->getCurrentOrganizationCode())) {
                // 官方organizationnotallowuse官方servicequotient
                return null;
            }
            // getto应servicequotient实body
            $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
            if (! $providerEntity) {
                return null;
            }

            // 先checkorganizationdownwhetheralready存into应configuration
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // if存intrue实configuration,returntrue实configuration
                return $existingConfig;
            }

            // not存ino clock才constructvirtualservicequotientconfiguration实body
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
     * getorganizationdown所haveservicequotientconfiguration.
     * @return ProviderConfigEntity[]
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array
    {
        return $this->serviceProviderConfigRepository->getAllByOrganization($dataIsolation);
    }

    /**
     * according toIDgetservicequotientconfiguration(notfilterorganization).
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        return $this->serviceProviderConfigRepository->getByIdWithoutOrganizationFilter($id);
    }

    /**
     * createvirtualservicequotientconfiguration实body(support所haveservicequotienttype).
     */
    private function createVirtualProviderConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, string $templateId): ProviderConfigEntity
    {
        $configEntity = new ProviderConfigEntity();

        // except delightful servicequotient,defaultstatusallisclose
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
     * handletemplateconfigurationupdatelogic.
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

        // 2. validatetemplateconfigurationnotcanfornull
        $config = $providerConfigEntity->getConfig();
        if ($config === null || $this->isProviderConfigEmpty($config)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // 3. getto应servicequotient实body
        $providerEntity = $this->providerRepository->getByCodeAndCategory($providerCode, $category);
        if (! $providerEntity) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // 4. use互斥lockpreventandhaircreate
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
            // 5. find本organizationdownsame provider_code and category configuration
            $existingConfig = $this->serviceProviderConfigRepository->findFirstByServiceProviderId($dataIsolation, $providerEntity->getId());
            if ($existingConfig) {
                // 6. 存inthenupdate
                return $this->updateProviderConfigData($dataIsolation, $existingConfig, $providerConfigEntity);
            }

            // 7. not存inthencreatenewconfiguration
            return $this->createNewTemplateConfig($dataIsolation, $providerEntity, $providerConfigEntity);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * handle普通configurationupdatelogic(原havelogic).
     */
    private function handleNormalConfigUpdate(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        // get现haveconfiguration实body
        $existingConfigEntity = $this->serviceProviderConfigRepository->getById($dataIsolation, $providerConfigEntity->getId());
        if ($existingConfigEntity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderConfigError);
        }

        // getto应 Provider infoconductbusinessrulevalidate
        $provider = $this->getProviderById($dataIsolation, $existingConfigEntity->getServiceProviderId());
        if ($provider === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ServiceProviderNotFound);
        }

        // supportmodify官方servicequotient
        /*if ($provider->getProviderType() === ProviderType::Official) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::SystemError);
        }*/

        // use统oneconfigurationupdatelogic
        return $this->updateProviderConfigData($dataIsolation, $existingConfigEntity, $providerConfigEntity);
    }

    /**
     * createnewtemplateconfiguration.
     */
    private function createNewTemplateConfig(ProviderDataIsolation $dataIsolation, ProviderEntity $providerEntity, ProviderConfigEntity $templateConfigEntity): ProviderConfigEntity
    {
        // createnewconfiguration实body
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
     * 统oneconfigurationdataupdatelogic.
     * handle脱敏datamerge,fieldupdateandsave操as.
     */
    private function updateProviderConfigData(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $existingConfig, ProviderConfigEntity $newConfigData): ProviderConfigEntity
    {
        // handle脱敏backconfigurationdata
        $processedConfig = $this->processDesensitizedConfig(
            $newConfigData->getConfig(),
            $existingConfig->getConfig()
        );

        // updateconfigurationdata
        $existingConfig->setConfig($processedConfig);

        // updateotherfield(ifhaveprovide)
        if ($newConfigData->getAlias()) {
            $existingConfig->setAlias($newConfigData->getAlias());
        }
        if ($newConfigData->getTranslate()) {
            $existingConfig->setTranslate($newConfigData->getTranslate());
        }

        $existingConfig->setUpdatedAt(new DateTime());
        $existingConfig->setSort($newConfigData->getSort());
        $existingConfig->setStatus($newConfigData->getStatus());
        // saveandreturn
        return $this->serviceProviderConfigRepository->save($dataIsolation, $existingConfig);
    }

    /**
     * checkProviderConfigItemconfigurationwhetherfornull(所havefieldallisnullvalue).
     */
    private function isProviderConfigEmpty(ProviderConfigItem $config): bool
    {
        // check所haveconfigurationfieldwhetherallfornull
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
