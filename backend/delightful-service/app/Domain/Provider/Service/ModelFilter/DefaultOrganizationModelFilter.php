<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

/**
 * defaultorganizationmodelfilter器implement.
 *
 * notconduct任何filter，直接returnoriginalmodellist
 * useatopen源versionor企业package未configurationo clockbacksolution
 */
class DefaultOrganizationModelFilter implements OrganizationBasedModelFilterInterface
{
    /**
     * defaultimplement：notconductfilter，return所have传入model.
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array
    {
        return $models;
    }

    /**
     * defaultimplement：所havemodelallcanuse.
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool
    {
        return true;
    }

    /**
     * defaultimplement：returnemptyarray，table示nothave特定modelbind.
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array
    {
        return [];
    }

    /**
     * defaultimplement：returnemptyarray，table示nothavemodelneed升level.
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array
    {
        return [];
    }
}
