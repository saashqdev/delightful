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
     * @return null|array<string, mixed> processresult，ifnotneedreturndatathenreturnnull
     */
    public function handle(MessageInterface $request): ?array;

    /**
     * settingtoolmanager.
     */
    public function setToolManager(MCPToolManager $toolManager): self;

    /**
     * gettoolmanager.
     */
    public function getToolManager(): MCPToolManager;

    /**
     * settingresourcemanager.
     */
    public function setResourceManager(MCPResourceManager $resourceManager): self;

    /**
     * getresourcemanager.
     */
    public function getResourceManager(): MCPResourceManager;

    /**
     * settingpromptmanager.
     */
    public function setPromptManager(MCPPromptManager $promptManager): self;

    /**
     * getpromptmanager.
     */
    public function getPromptManager(): MCPPromptManager;
}
