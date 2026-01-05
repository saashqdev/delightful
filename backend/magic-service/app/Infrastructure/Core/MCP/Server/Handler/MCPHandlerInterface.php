<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;
use App\Infrastructure\Core\MCP\Types\Message\Notification;
use App\Infrastructure\Core\MCP\Types\Message\Request;

interface MCPHandlerInterface
{
    public function handle(Notification|Request $request): ?MessageInterface;
}
