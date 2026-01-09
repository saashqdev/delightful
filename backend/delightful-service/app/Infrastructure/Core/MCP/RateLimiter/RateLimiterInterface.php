<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * speedratelimit器interface.
 */
interface RateLimiterInterface
{
    /**
     * checkcustomer端whetherallowexecuterequest.
     *
     * @throws InvalidParamsException whenrequest超passspeedratelimito clock
     */
    public function check(string $clientId, MessageInterface $request): void;

    /**
     * getwhenfrontlimitconfiguration.
     *
     * @return array<string, bool|int> containlimitconfigurationarray
     */
    public function getLimits(): array;
}
