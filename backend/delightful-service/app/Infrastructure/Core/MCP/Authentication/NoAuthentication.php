<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无authenticationimplement.
 * whensystemdesign要求have身份verifybutnotneedactualverify时use.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * verifyrequest的身份information.
     * in此implement中，始终allow所haverequestpass.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement，始终allow所haverequestpass
    }
}
