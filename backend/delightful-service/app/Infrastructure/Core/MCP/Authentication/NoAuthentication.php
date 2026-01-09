<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无authenticationimplement.
 * whensystemdesign要求有身份verify但不needactualverify时use.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * verify请求的身份information.
     * 在此implement中，始终allow所有请求pass.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement，始终allow所有请求pass
    }
}
