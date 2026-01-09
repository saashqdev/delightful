<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * noauthenticationimplement.
 * whensystemdesignrequirehavebodyshareverifybutnotneedactualverifyo clockuse.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * verifyrequestbodyshareinformation.
     * inthisimplementmiddle,alwaysallow haverequestpass.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement,alwaysallow haverequestpass
    }
}
