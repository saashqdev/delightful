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
 * MCPmethodprocess器interface.
 */
interface MethodHandlerInterface
{
    /**
     * processrequest并returnresult.
     *
     * @return null|array<string, mixed> processresult，if不needreturndata则returnnull
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
     * settingprompt管理器.
     */
    public function setPromptManager(MCPPromptManager $promptManager): self;

    /**
     * getprompt管理器.
     */
    public function getPromptManager(): MCPPromptManager;
}
