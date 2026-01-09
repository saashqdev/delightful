<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

/**
 * 默认的organizationmodelfilter器implement.
 *
 * 不进行任何filter，直接return原始modellist
 * 用于开源版本或企业包未configuration时的回退方案
 */
class DefaultOrganizationModelFilter implements OrganizationBasedModelFilterInterface
{
    /**
     * 默认implement：不进行filter，return所有传入的model.
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array
    {
        return $models;
    }

    /**
     * 默认implement：所有model都可用.
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool
    {
        return true;
    }

    /**
     * 默认implement：return空array，table示没有特定的model绑定.
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array
    {
        return [];
    }

    /**
     * 默认implement：return空array，table示没有model需要升级.
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array
    {
        return [];
    }
}
