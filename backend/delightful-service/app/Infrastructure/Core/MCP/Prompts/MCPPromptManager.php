<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCPpromptmanager.
 * whenfrontversionfornullimplement,onlyreturnnullcolumntable.
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
        // nullimplement,temporarynotregisteranyprompt
    }

    /**
     * getpromptcolumntable.
     * whenfrontfornullimplement,returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * getfingersetIDprompt.
     * whenfrontfornullimplement,alwaysreturnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * checkfingersetIDpromptwhetherexistsin.
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
