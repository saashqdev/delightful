<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * MCP身份verify接口.
 */
interface AuthenticationInterface
{
    /**
     * verifyrequest的身份info.
     *
     * @throws InvalidParamsException whenverifyfail时抛出
     */
    public function authenticate(MessageInterface $request): void;
}
