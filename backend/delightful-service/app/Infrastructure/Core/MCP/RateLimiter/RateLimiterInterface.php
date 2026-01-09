<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\RateLimiter;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 速率限制器interface.
 */
interface RateLimiterInterface
{
    /**
     * check客户端whetherallowexecuterequest.
     *
     * @throws InvalidParamsException whenrequest超过速率限制时
     */
    public function check(string $clientId, MessageInterface $request): void;

    /**
     * getwhen前的限制configuration.
     *
     * @return array<string, bool|int> contain限制configuration的array
     */
    public function getLimits(): array;
}
