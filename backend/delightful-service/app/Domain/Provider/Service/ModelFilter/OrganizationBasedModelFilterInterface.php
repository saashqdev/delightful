<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

use App\Domain\Provider\Entity\ProviderModelEntity;

/**
 * based onorganizationencoding的modelfilterserviceinterface.
 *
 * useat替代based onmodeltable visiblePackages field的filter逻辑
 * 企业packageimplement此interface，提供给开源packageconductmodelfilter
 */
interface OrganizationBasedModelFilterInterface
{
    /**
     * based onorganizationencodingfiltermodellist
     * 这是企业package提供给开源package的核corefiltermethod.
     *
     * @param string $organizationCode organizationencoding
     * @param array $models 待filter的modellist [modelId => ProviderModelEntity]
     * @return array filterback的modellist [modelId => ProviderModelEntity]
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array;

    /**
     * checkfinger定modelwhether对organization可use.
     *
     * @param string $organizationCode organizationencoding
     * @param string $modelIdentifier modelidentifier (如: gpt-4o)
     * @return bool whether可use
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool;

    /**
     * getorganizationcurrentsubscribeproductbind的所havemodelidentifier.
     *
     * @param string $organizationCode organizationencoding
     * @return array modelidentifierarray，for example: ['gpt-4o', 'claude-3', ...]
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array;

    /**
     * getorganizationneed升level才能use的modelIDlist.
     *
     * @param string $organizationCode organizationencoding
     * @return array need升level的modelIDarray，for example: ['gpt-4o-advanced', 'claude-3-opus', ...]
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array;
}
