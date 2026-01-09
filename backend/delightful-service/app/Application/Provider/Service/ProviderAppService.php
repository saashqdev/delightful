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

        // builddataisolationobject
        $dataIsolation = ProviderDataIsolation::create($organizationCode);

        // receivecollection haveuniqueoneservicequotientconfigurationID
        $configIds = array_unique(array_map(fn ($model) => $model->getServiceProviderConfigId(), $models));

        // batchquantitygetservicequotientactualbody(avoid嵌setquery)
        $providerEntities = $this->providerConfigDomainService->getProviderEntitiesByConfigIds($dataIsolation, $configIds);

        // batchquantitygetservicequotientconfigurationactualbody(useatgetalias)
        $configEntities = $this->providerConfigDomainService->getConfigByIdsWithoutOrganizationFilter($configIds);

        // receivecollection havegraphmarkpath按organizationencodingminutegroup(includemodelgraphmarkandservicequotientgraphmark)
        $iconsByOrg = [];
        $iconToModelMap = [];
        $iconToProviderMap = [];

        foreach ($models as $model) {
            // processmodelgraphmark
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

            // processservicequotientgraphmark
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

        // batchquantitygetgraphmarkURL
        $iconUrlMap = [];
        foreach ($iconsByOrg as $iconOrganizationCode => $icons) {
            $links = $this->fileDomainService->getLinks($iconOrganizationCode, array_unique($icons));
            $iconUrlMap[] = $links;
        }
        ! empty($iconUrlMap) && $iconUrlMap = array_merge(...$iconUrlMap);

        // updateservicequotientgraphmarkURLmapping
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
        // createDTOandsetgraphmarkURL
        $modelDTOs = [];
        foreach ($models as $model) {
            $modelDTO = new BeDelightfulModelDTO($model->toArray());

            $localizedModelName = $model->getLocalizedName($locale);
            $localizedModelDescription = $model->getLocalizedDescription($locale);

            // ifhaveinternationalizationnamethenuse,nothenmaintainoriginalname
            if (! empty($localizedModelName)) {
                $modelDTO->setName($localizedModelName);
            }
            $modelDTO->setDescription($localizedModelDescription);

            // setmodelgraphmarkURL
            $modelIcon = $model->getIcon();
            if (! empty($modelIcon) && isset($iconUrlMap[$modelIcon])) {
                $fileLink = $iconUrlMap[$modelIcon];
                if ($fileLink) {
                    $modelDTO->setIcon($fileLink->getUrl());
                }
            }

            // createservicequotientDTO
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
     * getservicequotientdisplayname.
     */
    private function getProviderDisplayName(
        ProviderEntity $providerEntity,
        ?ProviderConfigEntity $configEntity,
        bool $isRecommended,
        string $locale
    ): string {
        // 1. recommendedservicequotientpriority
        if ($isRecommended) {
            return $this->translator->trans('common.recommended');
        }

        // 2. customizeservicequotientandhavealias
        if ($this->isCustomProvider($providerEntity)
            && $configEntity
            && ! empty($configEntity->getAlias())) {
            return $configEntity->getAlias();
        }

        // 3. defaultuseinternationalizationname
        return $providerEntity->getLocalizedName($locale);
    }

    /**
     * judgewhetherforcustomizeservicequotient.
     */
    private function isCustomProvider(ProviderEntity $providerEntity): bool
    {
        return $providerEntity->getProviderCode() !== ProviderCode::Official;
    }
}
