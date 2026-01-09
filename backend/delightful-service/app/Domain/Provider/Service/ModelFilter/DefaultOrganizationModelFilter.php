<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ModelFilter;

/**
 * defaultorganizationmodelfilterdeviceimplement.
 *
 * notconductanyfilter,directlyreturnoriginalmodellist
 * useatopensourceversionorenterprisepackagenotconfigurationo clockbacksolution
 */
class DefaultOrganizationModelFilter implements OrganizationBasedModelFilterInterface
{
    /**
     * defaultimplement:notconductfilter,return havepass inmodel.
     */
    public function filterModelsByOrganization(string $organizationCode, array $models): array
    {
        return $models;
    }

    /**
     * defaultimplement: havemodelallcanuse.
     */
    public function isModelAvailableForOrganization(string $organizationCode, string $modelIdentifier): bool
    {
        return true;
    }

    /**
     * defaultimplement:returnemptyarray,tableshownothavespecificmodelbind.
     */
    public function getAvailableModelIdentifiers(string $organizationCode): array
    {
        return [];
    }

    /**
     * defaultimplement:returnemptyarray,tableshownothavemodelneedupgradelevel.
     */
    public function getUpgradeRequiredModelIds(string $organizationCode): array
    {
        return [];
    }
}
