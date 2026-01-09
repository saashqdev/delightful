<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace app\Application\Provider\Service;

use App\Application\Provider\DTO\BeDelightfulModelDTO;
use App\Application\Provider\DTO\BeDelightfulProviderDTO;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\Domain\Provider\Service\ProviderConfigDomainService;
use Hyperf\Contract\TranslatorInterface;

class ProviderAppService
{
    public function __construct(
        protected AdminProviderDomainService $adminProviderDomainService,
        protected ProviderConfigDomainService $providerConfigDomainService,
        protected FileDomainService $fileDomainService,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * Get be delightful display models and Delightful provider models visible to current organization.
     * @param string $organizationCode Organization code
     * @return BeDelightfulModelDTO[]
     */
    public function getBeDelightfulDisplayModelsForOrganization(string $organizationCode): array
    {
        $models = $this->adminProviderDomainService->getBeDelightfulDisplayModelsForOrganization($organizationCode);

        if (empty($models)) {
            return [];
        }

        // build数据隔离object
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // 收集所有唯一的service商configurationID
        $configIds = array_unique(array_map(fn ($model) => $model->getServiceProviderConfigId(), $models));

        // 批量getservice商实体（避免嵌套query）
        $providerEntities = $this->providerConfigDomainService->getProviderEntitiesByConfigIds($dataIsolation, $configIds);

        // 批量getservice商configuration实体（用于get别名）
        $configEntities = $this->providerConfigDomainService->getConfigByIdsWithoutOrganizationFilter($configIds);

        // 收集所有图标路径按organization编码分组（includemodel图标和service商图标）
        $iconsByOrg = [];
        $iconToModelMap = [];
        $iconToProviderMap = [];

        foreach ($models as $model) {
            // 处理model图标
            $modelIcon = $model->getIcon();
            if (empty($modelIcon)) {
                continue;
            }
            $iconOrganizationCode = substr($modelIcon, 0, strpos($modelIcon, '/'));
            if (! isset($iconsByOrg[$iconOrganizationCode])) {
                $iconsByOrg[$iconOrganizationCode] = [];
            }
            $iconsByOrg[$iconOrganizationCode][] = $modelIcon;
            if (! isset($iconToModelMap[$modelIcon])) {
                $iconToModelMap[$modelIcon] = [];
            }
            $iconToModelMap[$modelIcon][] = $model;

            // 处理service商图标
            $configId = $model->getServiceProviderConfigId();
            if (! isset($providerEntities[$configId])) {
                continue;
            }
            $providerIcon = $providerEntities[$configId]->getIcon();
            if (empty($providerIcon)) {
                continue;
            }
            $iconOrganizationCode = substr($providerIcon, 0, strpos($providerIcon, '/'));
            if (! isset($iconsByOrg[$iconOrganizationCode])) {
                $iconsByOrg[$iconOrganizationCode] = [];
            }
            $iconsByOrg[$iconOrganizationCode][] = $providerIcon;
            if (! isset($iconToProviderMap[$providerIcon])) {
                $iconToProviderMap[$providerIcon] = [];
            }
            $iconToProviderMap[$providerIcon][] = $configId;
        }

        // 批量get图标URL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $iconOrganizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($iconOrganizationCode, array_unique($icons));
            $iconUrlMap[] = $links;
        }
        ! empty($iconUrlMap) && $iconUrlMap = array_merge(...$iconUrlMap);

        // updateservice商图标URL映射
        $providerIconUrls = [];
        foreach ($iconToProviderMap as $icon => $configIds) {
            if (! isset($iconUrlMap[$icon])) {
                continue;
            }
            $fileLink = $iconUrlMap[$icon];
            if ($fileLink) {
                foreach ($configIds as $configId) {
                    $providerIconUrls[$configId] = $fileLink->getUrl();
                }
            }
        }
        $locale = $this->translator->getLocale();
        // createDTO并set图标URL
        $modelDTOs = [];
        foreach ($models as $model) {
            $modelDTO = new BeDelightfulModelDTO($model->toArray());

            $localizedModelName = $model->getLocalizedName($locale);
            $localizedModelDescription = $model->getLocalizedDescription($locale);

            // 如果有国际化name则use，否则保持原name
            if (! empty($localizedModelName)) {
                $modelDTO->setName($localizedModelName);
            }
            $modelDTO->setDescription($localizedModelDescription);

            // setmodel图标URL
            $modelIcon = $model->getIcon();
            if (! empty($modelIcon) && isset($iconUrlMap[$modelIcon])) {
                $fileLink = $iconUrlMap[$modelIcon];
                if ($fileLink) {
                    $modelDTO->setIcon($fileLink->getUrl());
                }
            }

            // createservice商DTO
            $configId = $model->getServiceProviderConfigId();
            $providerEntity = $providerEntities[$configId] ?? null;
            if ($providerEntity) {
                $isRecommended = $model->getConfig()?->isOfficialRecommended() ?? false;
                $configEntity = $configEntities[$configId] ?? null;
                $localizedName = $this->getProviderDisplayName($providerEntity, $configEntity, $isRecommended, $locale);
                $providerIconUrl = $providerIconUrls[$configId] ?? '';

                $providerDTO = new BeDelightfulProviderDTO([
                    'name' => $localizedName,
                    'icon' => $providerIconUrl,
                    'sort' => $model->getSort(),
                    'recommended' => $isRecommended,
                ]);
                $modelDTO->setProvider($providerDTO);
            }

            $modelDTOs[] = $modelDTO;
        }

        return $modelDTOs;
    }

    /**
     * getservice商显示name.
     */
    private function getProviderDisplayName(
        ProviderEntity $providerEntity,
        ?ProviderConfigEntity $configEntity,
        bool $isRecommended,
        string $locale
    ): string {
        // 1. 推荐service商优先
        if ($isRecommended) {
            return $this->translator->trans('common.recommended');
        }

        // 2. customizeservice商且有别名
        if ($this->isCustomProvider($providerEntity)
            && $configEntity
            && ! empty($configEntity->getAlias())) {
            return $configEntity->getAlias();
        }

        // 3. 默认use国际化name
        return $providerEntity->getLocalizedName($locale);
    }

    /**
     * 判断是否为customizeservice商.
     */
    private function isCustomProvider(ProviderEntity $providerEntity): bool
    {
        return $providerEntity->getProviderCode() !== ProviderCode::Official;
    }
}
