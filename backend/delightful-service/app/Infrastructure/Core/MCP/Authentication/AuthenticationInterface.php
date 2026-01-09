<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * MCPbodyshareverifyinterface.
 */
interface AuthenticationInterface
{
    /**
     * verifyrequestbodyshareinfo.
     *
     * @throws InvalidParamsException whenverifyfailo clockthrow
     */
    public function authenticate(MessageInterface $request): void;
}
