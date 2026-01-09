<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无限制的速率限制器implement.
 * 对任何request都不进行限制，适用于对performance要求较高或处于开发阶段的system.
 */
class NoRateLimiter extends AbstractRateLimiter
{
    /**
     * 是否启用速率限制.
     */
    protected bool $enabled = false;

    /**
     * getwhen前的限制configuration.
     * 对于无限制implement，所有限制均设为 PHP_INT_MAX.
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
     * 无限制的checkimplement，始终allowrequestpass.
     */
    protected function doCheck(string $clientId, MessageInterface $request): void
    {
        // nullimplement，始终allowrequestpass
    }
}
