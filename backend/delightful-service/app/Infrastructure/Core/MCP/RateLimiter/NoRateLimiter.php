<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无限制的speedrate限制器implement.
 * 对任何requestallnotconduct限制，适useat对performance要求more高or处at开hair阶segment的system.
 */
class NoRateLimiter extends AbstractRateLimiter
{
    /**
     * whetherenablespeedrate限制.
     */
    protected bool $enabled = false;

    /**
     * getwhenfront的限制configuration.
     * 对at无限制implement，所have限制均设为 PHP_INT_MAX.
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
