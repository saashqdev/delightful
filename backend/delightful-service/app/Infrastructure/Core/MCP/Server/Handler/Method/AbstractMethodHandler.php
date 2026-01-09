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
     * set工具管理器.
     */
    public function setToolManager(MCPToolManager $toolManager): self
    {
        $this->toolManager = $toolManager;
        return $this;
    }

    /**
     * get工具管理器.
     * @throws InternalErrorException 当工具管理器未set时抛出
     */
    public function getToolManager(): MCPToolManager
    {
        if (! isset($this->toolManager)) {
            throw new InternalErrorException('工具管理器(ToolManager)未set');
        }
        return $this->toolManager;
    }

    /**
     * set资源管理器.
     */
    public function setResourceManager(MCPResourceManager $resourceManager): self
    {
        $this->resourceManager = $resourceManager;
        return $this;
    }

    /**
     * get资源管理器.
     * @throws InternalErrorException 当资源管理器未set时抛出
     */
    public function getResourceManager(): MCPResourceManager
    {
        if (! isset($this->resourceManager)) {
            throw new InternalErrorException('资源管理器(ResourceManager)未set');
        }
        return $this->resourceManager;
    }

    /**
     * sethint管理器.
     */
    public function setPromptManager(MCPPromptManager $promptManager): self
    {
        $this->promptManager = $promptManager;
        return $this;
    }

    /**
     * gethint管理器.
     * @throws InternalErrorException 当hint管理器未set时抛出
     */
    public function getPromptManager(): MCPPromptManager
    {
        if (! isset($this->promptManager)) {
            throw new InternalErrorException('hint管理器(PromptManager)未set');
        }
        return $this->promptManager;
    }
}
