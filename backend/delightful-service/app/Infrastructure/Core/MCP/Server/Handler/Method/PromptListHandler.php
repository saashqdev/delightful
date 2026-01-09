<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * prompt列tablemethodprocess器.
 */
class PromptListHandler extends AbstractMethodHandler
{
    /**
     * processprompt列tablerequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'prompts' => $this->getPromptManager()->getPrompts(),
        ];
    }
}
