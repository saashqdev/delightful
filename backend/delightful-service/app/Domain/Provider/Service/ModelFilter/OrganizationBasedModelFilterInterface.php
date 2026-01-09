<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

use App\Domain\Provider\Entity\ProviderModelEntity;

/**
 * based onorganizationencodingmodelfilterserviceinterface.
 *
 * useatsubstitutebased onmodeltable visiblePackages fieldfilterlogic
 * enterprisepackageimplementthisinterface,providegiveopensourcepackageconductmodelfilter
 */
interface OrganizationBasedModelFilterInterface
{
    /**
     * based onorganizationencodingfiltermodellist
     * thisisenterprisepackageprovidegiveopensourcepackagecorecorefiltermethod.
     *
     * @param string $organizationCode organizationencoding
     * @param array $models pendingfiltermodellist [modelId => ProviderModelEntity]
     * @return array filterbackmodellist [modelId => ProviderModelEntity]
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array;

    /**
     * checkfingersetmodelwhethertoorganizationcanuse.
     *
     * @param string $organizationCode organizationencoding
     * @param string $modelIdentifier modelidentifier (like: gpt-4o)
     * @return bool whethercanuse
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool;

    /**
     * getorganizationcurrentsubscribeproductbind havemodelidentifier.
     *
     * @param string $organizationCode organizationencoding
     * @return array modelidentifierarray,for example: ['gpt-4o', 'claude-3', ...]
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array;

    /**
     * getorganizationneed升levelonlycanusemodelIDlist.
     *
     * @param string $organizationCode organizationencoding
     * @return array need升levelmodelIDarray,for example: ['gpt-4o-advanced', 'claude-3-opus', ...]
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array;
}
