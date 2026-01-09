<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

/**
 * officialorganizationtoolcategory
 * systemonemanageofficialorganizationrelatedcloseconfigurationandjudgelogic.
 */
class OfficialOrganizationUtil
{
    /**
     * getofficialorganizationencoding
     */
    public static function getOfficialOrganizationCode(): string
    {
        return config('service_provider.office_organization', '');
    }

    /**
     * judgewhetherforofficialorganization.
     */
    public static function isOfficialOrganization(string $organizationCode): bool
    {
        return $organizationCode === self::getOfficialOrganizationCode();
    }

    /**
     * getcontainofficialorganizationininsideorganizationencodingarray.
     * @param string $currentOrganizationCode whenfrontorganizationencoding
     * @return array goreloadbackorganizationencodingarray
     */
    public static function getOrganizationCodesWithOfficial(string $currentOrganizationCode): array
    {
        $officialOrganizationCode = self::getOfficialOrganizationCode();
        return array_filter(array_unique([$currentOrganizationCode, $officialOrganizationCode]));
    }

    /**
     * checkofficialorganizationencodingwhetheralreadyconfiguration.
     */
    public static function hasOfficialOrganization(): bool
    {
        return ! empty(self::getOfficialOrganizationCode());
    }
}
