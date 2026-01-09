<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\SSRF;

use Throwable;

/**
 * SSRF防御工具类.
 *
 * use示例：
 * // 简单use，defaultconfiguration
 * $safeUrl = SSRFUtil::getSafeUrl('https://example.com');
 *
 * // customizeparameter
 * $safeUrl = SSRFUtil::getSafeUrl('https://example.com', replaceIp: false, allowRedirect: true);
 *
 * // 高级configuration
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
     * getSSRF防御安全链接.
     *
     * @param string $url needcheck的URL
     * @param array $blackList blacklistIP或域名
     * @param array $whiteList 白名单IP或域名
     * @param array $allowProtocols allow的协议
     * @param bool $replaceIp 是否替换为IPaccess
     * @param bool $allowRedirect 是否allow重定向
     * @return string 安全的URL
     * @throws Exception\SSRFException 当URL不安全时throwexception
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
     * checkURL是否安全（不抛exception，return布尔value）.
     *
     * @param string $url needcheck的URL
     * @param array $blackList blacklistIP或域名
     * @param array $whiteList 白名单IP或域名
     * @param array $allowProtocols allow的协议
     * @param bool $replaceIp 是否替换为IPaccess
     * @param bool $allowRedirect 是否allow重定向
     * @return bool 是否安全
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
     * @return null|string IP地址或null（如果parsefail）
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
