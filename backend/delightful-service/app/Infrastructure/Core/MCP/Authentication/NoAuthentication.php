<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * noauthenticationimplement.
 * whensystemdesign要求have身shareverifybutnotneedactualverifyo clockuse.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * verifyrequest身shareinformation.
     * in此implementmiddle，始终allow所haverequestpass.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement，始终allow所haverequestpass
    }
}
