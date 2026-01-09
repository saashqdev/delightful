<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * toolcallmethodprocess器.
 */
class ToolCallHandler extends AbstractMethodHandler
{
    /**
     * processtoolcallrequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        $params = $request->getParams();
        if (! isset($params['name'])) {
            throw new InvalidParamsException('Tool name is required');
        }

        $toolName = $params['name'];

        if (! $this->getToolManager()->hasTool($toolName)) {
            throw new InvalidParamsException("Tool '{$toolName}' not found");
        }

        $tool = $this->getToolManager()->getTool($toolName);
        $result = $tool->call($params['arguments'] ?? []);

        return [
            'content' => [['type' => 'text', 'text' => $result]],
        ];
    }
}
