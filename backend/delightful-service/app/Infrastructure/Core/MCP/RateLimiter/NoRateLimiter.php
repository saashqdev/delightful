<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无限制speedrate限制器implement.
 * to任何requestallnotconduct限制，适useattoperformance要求more高or处atopenhair阶segmentsystem.
 */
class NoRateLimiter extends AbstractRateLimiter
{
    /**
     * whetherenablespeedrate限制.
     */
    protected bool $enabled = false;

    /**
     * getwhenfront限制configuration.
     * toat无限制implement，所have限制均设for PHP_INT_MAX.
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
     * 无限制checkimplement，始终allowrequestpass.
     */
    protected function doCheck(string $clientId, MessageInterface $request): void
    {
        // nullimplement，始终allowrequestpass
    }
}
