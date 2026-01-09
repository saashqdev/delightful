<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

use App\Domain\Provider\Entity\ProviderModelEntity;

/**
 * based onorganization编码的modelfilterservice接口.
 *
 * 用于替代based onmodeltable visiblePackages field的filter逻辑
 * 企业包implement此接口，提供给开源包进行modelfilter
 */
interface OrganizationBasedModelFilterInterface
{
    /**
     * based onorganization编码filtermodellist
     * 这是企业包提供给开源包的核心filtermethod.
     *
     * @param string $organizationCode organization编码
     * @param array $models 待filter的modellist [modelId => ProviderModelEntity]
     * @return array filter后的modellist [modelId => ProviderModelEntity]
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array;

    /**
     * check指定model是否对organization可用.
     *
     * @param string $organizationCode organization编码
     * @param string $modelIdentifier model标识符 (如: gpt-4o)
     * @return bool 是否可用
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool;

    /**
     * getorganizationcurrentsubscribe产品绑定的所有model标识符.
     *
     * @param string $organizationCode organization编码
     * @return array model标识符array，for example: ['gpt-4o', 'claude-3', ...]
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array;

    /**
     * getorganizationneed升级才能use的modelIDlist.
     *
     * @param string $organizationCode organization编码
     * @return array need升级的modelIDarray，for example: ['gpt-4o-advanced', 'claude-3-opus', ...]
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array;
}
