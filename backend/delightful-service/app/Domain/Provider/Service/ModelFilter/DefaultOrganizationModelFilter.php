<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

/**
 * default的organizationmodelfilter器implement.
 *
 * notconduct任何filter，直接returnoriginalmodellist
 * useat开源versionor企业package未configuration时的回退solution
 */
class DefaultOrganizationModelFilter implements OrganizationBasedModelFilterInterface
{
    /**
     * defaultimplement：notconductfilter，return所have传入的model.
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array
    {
        return $models;
    }

    /**
     * defaultimplement：所havemodelall可use.
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool
    {
        return true;
    }

    /**
     * defaultimplement：return空array，table示nothave特定的modelbind.
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array
    {
        return [];
    }

    /**
     * defaultimplement：return空array，table示nothavemodelneed升级.
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array
    {
        return [];
    }
}
