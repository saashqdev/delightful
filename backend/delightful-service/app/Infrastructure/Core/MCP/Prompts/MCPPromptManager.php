<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCPprompt管理器.
 * when前version为null实现，仅returnnull列表.
 */
class MCPPromptManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $prompts = [];

    /**
     * 注册prompt.
     * when前为null实现.
     */
    public function registerPrompt(array $prompt): void
    {
        // null实现，暂不注册任何prompt
    }

    /**
     * getprompt列表.
     * when前为null实现，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * get指定ID的prompt.
     * when前为null实现，始终returnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * 检查指定ID的prompt是否存在.
     */
    public function hasPrompt(string $id): bool
    {
        return isset($this->prompts[$id]);
    }

    /**
     * 检查是否没有任何prompt.
     */
    public function isEmpty(): bool
    {
        return empty($this->prompts);
    }
}
