<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * IP address utility.
 */
class IpUtil
{
    /**
     * Get client IP address.
     *
     * @param RequestInterface $request HTTP request object
     * @return null|string Client IP address
     */
    public static function getClientIpAddress(RequestInterface $request): ?string
    {
        // Priority order: X-Real-IP -> X-Forwarded-For -> remote_addr
        $realIp = $request->getHeaderLine('x-real-ip');
        if (! empty($realIp)) {
            return $realIp;
        }

        $forwardedFor = $request->getHeaderLine('x-forwarded-for');
        if (! empty($forwardedFor)) {
            // X-Forwarded-For may contain multiple IPs; take the first one
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        $serverParams = $request->getServerParams();
        return $serverParams['remote_addr'] ?? null;
    }
}
