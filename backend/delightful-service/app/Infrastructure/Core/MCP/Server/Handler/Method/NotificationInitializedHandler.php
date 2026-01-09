<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * notifyinitializemethodprocess器.
 */
class NotificationInitializedHandler extends AbstractMethodHandler
{
    /**
     * processnotifyinitializerequest.
     * 不needreturn数据.
     */
    public function handle(MessageInterface $request): ?array
    {
        return null;
    }
}
