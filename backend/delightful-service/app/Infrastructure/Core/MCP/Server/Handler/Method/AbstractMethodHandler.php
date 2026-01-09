<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Exception\InternalErrorException;
use App\Infrastructure\Core\MCP\Prompts\MCPPromptManager;
use App\Infrastructure\Core\MCP\Resources\MCPResourceManager;
use App\Infrastructure\Core\MCP\Tools\MCPToolManager;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * MCPmethodprocess器抽象基类.
 */
abstract class AbstractMethodHandler implements MethodHandlerInterface
{
    protected LoggerInterface $logger;

    private MCPToolManager $toolManager;

    private MCPResourceManager $resourceManager;

    private MCPPromptManager $promptManager;

    public function __construct(
        protected ContainerInterface $container
    ) {
        $this->logger = $this->container->get(LoggerFactory::class)
            ->get('MCPMethodHandler');
    }

    /**
     * settoolmanager.
     */
    public function setToolManager(MCPToolManager $toolManager): self
    {
        $this->toolManager = $toolManager;
        return $this;
    }

    /**
     * gettoolmanager.
     * @throws InternalErrorException 当toolmanager未set时throw
     */
    public function getToolManager(): MCPToolManager
    {
        if (! isset($this->toolManager)) {
            throw new InternalErrorException('toolmanager(ToolManager)未set');
        }
        return $this->toolManager;
    }

    /**
     * set资源manager.
     */
    public function setResourceManager(MCPResourceManager $resourceManager): self
    {
        $this->resourceManager = $resourceManager;
        return $this;
    }

    /**
     * get资源manager.
     * @throws InternalErrorException 当资源manager未set时throw
     */
    public function getResourceManager(): MCPResourceManager
    {
        if (! isset($this->resourceManager)) {
            throw new InternalErrorException('资源manager(ResourceManager)未set');
        }
        return $this->resourceManager;
    }

    /**
     * sethintmanager.
     */
    public function setPromptManager(MCPPromptManager $promptManager): self
    {
        $this->promptManager = $promptManager;
        return $this;
    }

    /**
     * gethintmanager.
     * @throws InternalErrorException 当hintmanager未set时throw
     */
    public function getPromptManager(): MCPPromptManager
    {
        if (! isset($this->promptManager)) {
            throw new InternalErrorException('hintmanager(PromptManager)未set');
        }
        return $this->promptManager;
    }
}
