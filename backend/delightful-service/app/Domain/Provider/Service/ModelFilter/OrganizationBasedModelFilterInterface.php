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
 * 用于替代based onmodeltable visiblePackages field的filter逻辑
 * 企业packageimplement此interface，提供给开源package进行modelfilter
 */
interface OrganizationBasedModelFilterInterface
{
    /**
     * based onorganizationencodingfiltermodellist
     * 这是企业package提供给开源package的核心filtermethod.
     *
     * @param string $organizationCode organizationencoding
     * @param array $models 待filter的modellist [modelId => ProviderModelEntity]
     * @return array filter后的modellist [modelId => ProviderModelEntity]
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array;

    /**
     * check指定model是否对organization可用.
     *
     * @param string $organizationCode organizationencoding
     * @param string $modelIdentifier model标识符 (如: gpt-4o)
     * @return bool 是否可用
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool;

    /**
     * getorganizationcurrentsubscribeproductbind的所有model标识符.
     *
     * @param string $organizationCode organizationencoding
     * @return array model标识符array，for example: ['gpt-4o', 'claude-3', ...]
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array;

    /**
     * getorganizationneed升级才能use的modelIDlist.
     *
     * @param string $organizationCode organizationencoding
     * @return array need升级的modelIDarray，for example: ['gpt-4o-advanced', 'claude-3-opus', ...]
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array;
}
