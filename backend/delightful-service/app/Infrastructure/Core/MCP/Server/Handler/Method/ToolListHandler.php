<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * tool列tablemethod处理器.
 */
class ToolListHandler extends AbstractMethodHandler
{
    /**
     * 处理tool列table请求.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'tools' => $this->getToolManager()->getToolSchemas(),
        ];
    }
}
