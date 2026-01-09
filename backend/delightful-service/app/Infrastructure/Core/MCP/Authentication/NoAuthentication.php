<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * noauthenticationimplement.
 * whensystemdesignrequirehave身shareverifybutnotneedactualverifyo clockuse.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * verifyrequest身shareinformation.
     * inthisimplementmiddle,始终allow所haverequestpass.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement,始终allow所haverequestpass
    }
}
