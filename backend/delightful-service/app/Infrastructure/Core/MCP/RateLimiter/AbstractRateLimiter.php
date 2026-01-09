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
     * each分钟most大request数.
     */
    protected int $maxRequestsPerMinute = 60;

    /**
     * each小时most大request数.
     */
    protected int $maxRequestsPerHour = 1000;

    /**
     * each天most大request数.
     */
    protected int $maxRequestsPerDay = 5000;

    /**
     * whetherenable速率限制.
     */
    protected bool $enabled = true;

    /**
     * check客户端whetherallowexecuterequest.
     */
    public function check(string $clientId, MessageInterface $request): void
    {
        if (! $this->enabled) {
            return;
        }

        // initializerequestnotconduct限制
        if ($request->getMethod() === 'initialize') {
            return;
        }

        // executespecific的速率限制check
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
     * 由子类implementspecific逻辑.
     */
    abstract protected function doCheck(string $clientId, MessageInterface $request): void;
}
