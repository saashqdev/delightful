<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Authentication;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 无认证implement.
 * whensystemdesign要求有身份验证但不need实际验证时use.
 */
class NoAuthentication implements AuthenticationInterface
{
    /**
     * 验证请求的身份information.
     * 在此implement中，始终允许所有请求通过.
     */
    public function authenticate(MessageInterface $request): void
    {
        // nullimplement，始终允许所有请求通过
    }
}
