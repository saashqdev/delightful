<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCPpromptmanager.
 * whenfrontversion为nullimplement，仅returnnullcolumn表.
 */
class MCPPromptManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $prompts = [];

    /**
     * registerprompt.
     * whenfront为nullimplement.
     */
    public function registerPrompt(array $prompt): void
    {
        // nullimplement，暂notregister任何prompt
    }

    /**
     * getpromptcolumn表.
     * whenfront为nullimplement，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * getfinger定ID的prompt.
     * whenfront为nullimplement，始终returnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * checkfinger定ID的promptwhether存in.
     */
    public function hasPrompt(string $id): bool
    {
        return isset($this->prompts[$id]);
    }

    /**
     * checkwhethernothave任何prompt.
     */
    public function isEmpty(): bool
    {
        return empty($this->prompts);
    }
}
