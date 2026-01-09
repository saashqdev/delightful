<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Prompts\MCPPromptManager;
use App\Infrastructure\Core\MCP\Resources\MCPResourceManager;
use App\Infrastructure\Core\MCP\Tools\MCPToolManager;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * MCP方法处理器接口.
 */
interface MethodHandlerInterface
{
    /**
     * 处理请求并return结果.
     *
     * @return null|array<string, mixed> 处理结果，如果不需要returndata则returnnull
     */
    public function handle(MessageInterface $request): ?array;

    /**
     * settingtool管理器.
     */
    public function setToolManager(MCPToolManager $toolManager): self;

    /**
     * gettool管理器.
     */
    public function getToolManager(): MCPToolManager;

    /**
     * setting资源管理器.
     */
    public function setResourceManager(MCPResourceManager $resourceManager): self;

    /**
     * get资源管理器.
     */
    public function getResourceManager(): MCPResourceManager;

    /**
     * setting提示管理器.
     */
    public function setPromptManager(MCPPromptManager $promptManager): self;

    /**
     * get提示管理器.
     */
    public function getPromptManager(): MCPPromptManager;
}
