<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

/**
 * default的organizationmodelfilter器implement.
 *
 * 不进行任何filter，直接returnoriginalmodellist
 * 用于开源version或企业package未configuration时的回退方案
 */
class DefaultOrganizationModelFilter implements OrganizationBasedModelFilterInterface
{
    /**
     * defaultimplement：不进行filter，return所有传入的model.
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array
    {
        return $models;
    }

    /**
     * defaultimplement：所有model都可用.
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool
    {
        return true;
    }

    /**
     * defaultimplement：return空array，table示没有特定的model绑定.
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array
    {
        return [];
    }

    /**
     * defaultimplement：return空array，table示没有modelneed升级.
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array
    {
        return [];
    }
}
