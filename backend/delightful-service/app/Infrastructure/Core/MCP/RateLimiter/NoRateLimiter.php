<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * nolimitspeedratelimit器implement.
 * toanyrequestallnotconductlimit,适useattoperformancerequiremorehighor处atopenhair阶segmentsystem.
 */
class NoRateLimiter extends AbstractRateLimiter
{
    /**
     * whetherenablespeedratelimit.
     */
    protected bool $enabled = false;

    /**
     * getwhenfrontlimitconfiguration.
     * toatnolimitimplement,所havelimit均设for PHP_INT_MAX.
     */
    public function getLimits(): array
    {
        return [
            'enabled' => false,
            'rpm' => PHP_INT_MAX,
            'rph' => PHP_INT_MAX,
            'rpd' => PHP_INT_MAX,
        ];
    }

    /**
     * nolimitcheckimplement,alwaysallowrequestpass.
     */
    protected function doCheck(string $clientId, MessageInterface $request): void
    {
        // nullimplement,alwaysallowrequestpass
    }
}
