<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\SSRF;

use Throwable;

/**
 * SSRF防御toolcategory.
 *
 * useexample：
 * // 简单use，defaultconfiguration
 * $safeUrl = SSRFUtil::getSafeUrl('https://example.com');
 *
 * // customizeparameter
 * $safeUrl = SSRFUtil::getSafeUrl('https://example.com', replaceIp: false, allowRedirect: true);
 *
 * // 高levelconfiguration
 * $safeUrl = SSRFUtil::getSafeUrl(
 *     'https://example.com',
 *     blackList: ['192.168.1.1'],
 *     whiteList: ['trusted.example.com'],
 *     replaceIp: false
 * );
 */
class SSRFUtil
{
    /**
     * getSSRF防御securitylink.
     *
     * @param string $url needcheck的URL
     * @param array $blackList blacklistIPor域名
     * @param array $whiteList 白名单IPor域名
     * @param array $allowProtocols allow的agreement
     * @param bool $replaceIp whether替换为IPaccess
     * @param bool $allowRedirect whetherallow重定to
     * @return string security的URL
     * @throws Exception\SSRFException whenURLnotsecurityo clockthrowexception
     */
    public static function getSafeUrl(
        string $url,
        array $blackList = [],
        array $whiteList = [],
        array $allowProtocols = ['http', 'https'],
        bool $replaceIp = true,
        bool $allowRedirect = false
    ): string {
        $options = new SSRFDefenseOptions(
            $blackList,
            $whiteList,
            $allowProtocols,
            $replaceIp,
            $allowRedirect
        );

        $defense = new SSRFDefense($url, $options);
        return $defense->getSafeUrl($allowRedirect);
    }

    /**
     * checkURLwhethersecurity（not抛exception，return布尔value）.
     *
     * @param string $url needcheck的URL
     * @param array $blackList blacklistIPor域名
     * @param array $whiteList 白名单IPor域名
     * @param array $allowProtocols allow的agreement
     * @param bool $replaceIp whether替换为IPaccess
     * @param bool $allowRedirect whetherallow重定to
     * @return bool whethersecurity
     */
    public static function isSafeUrl(
        string $url,
        array $blackList = [],
        array $whiteList = [],
        array $allowProtocols = ['http', 'https'],
        bool $replaceIp = true,
        bool $allowRedirect = false
    ): bool {
        try {
            self::getSafeUrl($url, $blackList, $whiteList, $allowProtocols, $replaceIp, $allowRedirect);
            return true;
        } catch (Exception\SSRFException $e) {
            return false;
        }
    }

    /**
     * getURL对应的IP.
     *
     * @param string $url URL
     * @return null|string IPground址ornull（ifparsefail）
     */
    public static function getUrlIp(string $url): ?string
    {
        try {
            $options = new SSRFDefenseOptions();
            $defense = new SSRFDefense($url, $options);
            return $defense->getIp();
        } catch (Throwable $e) {
            return null;
        }
    }
}
