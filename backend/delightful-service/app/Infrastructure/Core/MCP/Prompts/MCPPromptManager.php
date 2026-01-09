<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCPpromptmanager.
 * whenfrontversionfornullimplement，仅returnnullcolumn表.
 */
class MCPPromptManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $prompts = [];

    /**
     * registerprompt.
     * whenfrontfornullimplement.
     */
    public function registerPrompt(array $prompt): void
    {
        // nullimplement，暂notregisteranyprompt
    }

    /**
     * getpromptcolumn表.
     * whenfrontfornullimplement，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * getfinger定IDprompt.
     * whenfrontfornullimplement，始终returnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * checkfinger定IDpromptwhether存in.
     */
    public function hasPrompt(string $id): bool
    {
        return isset($this->prompts[$id]);
    }

    /**
     * checkwhethernothaveanyprompt.
     */
    public function isEmpty(): bool
    {
        return empty($this->prompts);
    }
}
