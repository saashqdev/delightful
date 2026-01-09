<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 提示get方法处理器.
 */
class PromptGetHandler extends AbstractMethodHandler
{
    /**
     * 处理提示get请求.
     */
    public function handle(MessageInterface $request): ?array
    {
        $params = $request->getParams();
        if (! isset($params['id'])) {
            throw new InvalidParamsException('Prompt ID is required');
        }

        $prompt = $this->getPromptManager()->getPrompt($params['id']);
        if ($prompt === null) {
            throw new InvalidParamsException("Prompt '{$params['id']}' not found");
        }

        return [
            'prompt' => $prompt,
        ];
    }
}
