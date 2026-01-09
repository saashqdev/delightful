<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 抽象速率限制器基类.
 */
abstract class AbstractRateLimiter implements RateLimiterInterface
{
    /**
     * 每分钟最大request数.
     */
    protected int $maxRequestsPerMinute = 60;

    /**
     * 每小时最大request数.
     */
    protected int $maxRequestsPerHour = 1000;

    /**
     * 每天最大request数.
     */
    protected int $maxRequestsPerDay = 5000;

    /**
     * 是否启用速率限制.
     */
    protected bool $enabled = true;

    /**
     * check客户端是否allowexecuterequest.
     */
    public function check(string $clientId, MessageInterface $request): void
    {
        if (! $this->enabled) {
            return;
        }

        // initializerequest不进行限制
        if ($request->getMethod() === 'initialize') {
            return;
        }

        // execute具体的速率限制check
        $this->doCheck($clientId, $request);
    }

    /**
     * getcurrent的限制configuration.
     */
    public function getLimits(): array
    {
        return [
            'enabled' => $this->enabled,
            'rpm' => $this->maxRequestsPerMinute,
            'rph' => $this->maxRequestsPerHour,
            'rpd' => $this->maxRequestsPerDay,
        ];
    }

    /**
     * actualexecute的速率限制check.
     * 由子类implement具体逻辑.
     */
    abstract protected function doCheck(string $clientId, MessageInterface $request): void;
}
