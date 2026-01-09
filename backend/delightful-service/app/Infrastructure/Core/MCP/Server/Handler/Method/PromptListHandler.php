<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * promptcolumntablemethodprocessdevice.
 */
class PromptListHandler extends AbstractMethodHandler
{
    /**
     * processpromptcolumntablerequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'prompts' => $this->getPromptManager()->getPrompts(),
        ];
    }
}
