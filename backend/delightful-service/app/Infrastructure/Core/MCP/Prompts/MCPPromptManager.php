<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Prompts;

/**
 * MCP提示管理器.
 * 当前version为空实现，仅return空列表.
 */
class MCPPromptManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $prompts = [];

    /**
     * 注册提示.
     * 当前为空实现.
     */
    public function registerPrompt(array $prompt): void
    {
        // 空实现，暂不注册任何提示
    }

    /**
     * get提示列表.
     * 当前为空实现，return空数组.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPrompts(): array
    {
        return [];
    }

    /**
     * get指定ID的提示.
     * 当前为空实现，始终returnnull.
     */
    public function getPrompt(string $id): ?array
    {
        return null;
    }

    /**
     * 检查指定ID的提示是否存在.
     */
    public function hasPrompt(string $id): bool
    {
        return isset($this->prompts[$id]);
    }

    /**
     * 检查是否没有任何提示.
     */
    public function isEmpty(): bool
    {
        return empty($this->prompts);
    }
}
