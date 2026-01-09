<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

/**
 * 官方organizationtoolcategory
 * 统一管理官方organization相关的configuration和判断逻辑.
 */
class OfficialOrganizationUtil
{
    /**
     * get官方organizationencoding
     */
    public static function getOfficialOrganizationCode(): string
    {
        return config('service_provider.office_organization', '');
    }

    /**
     * 判断whether为官方organization.
     */
    public static function isOfficialOrganization(string $organizationCode): bool
    {
        return $organizationCode === self::getOfficialOrganizationCode();
    }

    /**
     * getcontain官方organizationininside的organizationencodingarray.
     * @param string $currentOrganizationCode whenfrontorganizationencoding
     * @return array 去重back的organizationencodingarray
     */
    public static function getOrganizationCodesWithOfficial(string $currentOrganizationCode): array
    {
        $officialOrganizationCode = self::getOfficialOrganizationCode();
        return array_filter(array_unique([$currentOrganizationCode, $officialOrganizationCode]));
    }

    /**
     * check官方organizationencodingwhether已configuration.
     */
    public static function hasOfficialOrganization(): bool
    {
        return ! empty(self::getOfficialOrganizationCode());
    }
}
