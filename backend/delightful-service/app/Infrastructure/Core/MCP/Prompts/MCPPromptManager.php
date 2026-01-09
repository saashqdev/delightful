<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCPpromptmanager.
 * when前version为nullimplement，仅returnnull列表.
 */
class MCPPromptManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $prompts = [];

    /**
     * registerprompt.
     * when前为nullimplement.
     */
    public function registerPrompt(array $prompt): void
    {
        // nullimplement，暂notregister任何prompt
    }

    /**
     * getprompt列表.
     * when前为nullimplement，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * get指定ID的prompt.
     * when前为nullimplement，始终returnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * check指定ID的promptwhether存in.
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
