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
 * useat替代based onmodeltable visiblePackages fieldfilterlogic
 * 企业packageimplementthisinterface,providegiveopen源packageconductmodelfilter
 */
interface OrganizationBasedModelFilterInterface
{
    /**
     * based onorganizationencodingfiltermodellist
     * thisis企业packageprovidegiveopen源package核corefiltermethod.
     *
     * @param string $organizationCode organizationencoding
     * @param array $models 待filtermodellist [modelId => ProviderModelEntity]
     * @return array filterbackmodellist [modelId => ProviderModelEntity]
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array;

    /**
     * checkfinger定modelwhethertoorganizationcanuse.
     *
     * @param string $organizationCode organizationencoding
     * @param string $modelIdentifier modelidentifier (如: gpt-4o)
     * @return bool whethercanuse
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool;

    /**
     * getorganizationcurrentsubscribeproductbind所havemodelidentifier.
     *
     * @param string $organizationCode organizationencoding
     * @return array modelidentifierarray,for example: ['gpt-4o', 'claude-3', ...]
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array;

    /**
     * getorganizationneed升level才canusemodelIDlist.
     *
     * @param string $organizationCode organizationencoding
     * @return array need升levelmodelIDarray,for example: ['gpt-4o-advanced', 'claude-3-opus', ...]
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array;
}
