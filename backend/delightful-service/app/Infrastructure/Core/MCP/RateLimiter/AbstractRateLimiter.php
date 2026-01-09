<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 抽象speedrate限制器基category.
 */
abstract class AbstractRateLimiter implements RateLimiterInterface
{
    /**
     * eachminute钟most大request数.
     */
    protected int $maxRequestsPerMinute = 60;

    /**
     * eachhourmost大request数.
     */
    protected int $maxRequestsPerHour = 1000;

    /**
     * eachdaymost大request数.
     */
    protected int $maxRequestsPerDay = 5000;

    /**
     * whetherenablespeedrate限制.
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

        // executespecific的speedrate限制check
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
     * actualexecute的speedrate限制check.
     * 由子categoryimplementspecific逻辑.
     */
    abstract protected function doCheck(string $clientId, MessageInterface $request): void;
}
