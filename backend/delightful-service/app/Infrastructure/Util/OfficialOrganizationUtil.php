<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

/**
 * 官方organizationtool类
 * 统一管理官方organization相关的configuration和判断逻辑.
 */
class OfficialOrganizationUtil
{
    /**
     * get官方organization编码
     */
    public static function getOfficialOrganizationCode(): string
    {
        return config('service_provider.office_organization', '');
    }

    /**
     * 判断是否为官方organization.
     */
    public static function isOfficialOrganization(string $organizationCode): bool
    {
        return $organizationCode === self::getOfficialOrganizationCode();
    }

    /**
     * get包含官方organization在内的organization编码array.
     * @param string $currentOrganizationCode when前organization编码
     * @return array 去重后的organization编码array
     */
    public static function getOrganizationCodesWithOfficial(string $currentOrganizationCode): array
    {
        $officialOrganizationCode = self::getOfficialOrganizationCode();
        return array_filter(array_unique([$currentOrganizationCode, $officialOrganizationCode]));
    }

    /**
     * 检查官方organization编码是否已configuration.
     */
    public static function hasOfficialOrganization(): bool
    {
        return ! empty(self::getOfficialOrganizationCode());
    }
}
