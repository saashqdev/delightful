<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * abstractspeedratelimitdevice basedcategory.
 */
abstract class AbstractRateLimiter implements RateLimiterInterface
{
    /**
     * eachminute钟mostbigrequest数.
     */
    protected int $maxRequestsPerMinute = 60;

    /**
     * eachhourmostbigrequest数.
     */
    protected int $maxRequestsPerHour = 1000;

    /**
     * eachdaymostbigrequest数.
     */
    protected int $maxRequestsPerDay = 5000;

    /**
     * whetherenablespeedratelimit.
     */
    protected bool $enabled = true;

    /**
     * checkcustomer端whetherallowexecuterequest.
     */
    public function check(string $clientId, MessageInterface $request): void
    {
        if (! $this->enabled) {
            return;
        }

        // initializerequestnotconductlimit
        if ($request->getMethod() === 'initialize') {
            return;
        }

        // executespecificspeedratelimitcheck
        $this->doCheck($clientId, $request);
    }

    /**
     * getcurrentlimitconfiguration.
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
     * actualexecutespeedratelimitcheck.
     * by子categoryimplementspecificlogic.
     */
    abstract protected function doCheck(string $clientId, MessageInterface $request): void;
}
