<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * MCPmethodprocess器工厂.
 */
class MethodHandlerFactory
{
    /**
     * methodprocess器映射，method名 => process器类名.
     *
     * @var array<string, class-string<MethodHandlerInterface>>
     */
    private static array $methodHandlerMap = [
        'initialize' => InitializeHandler::class,
        'tools/call' => ToolCallHandler::class,
        'tools/list' => ToolListHandler::class,
        'resources/list' => ResourceListHandler::class,
        'resources/read' => ResourceReadHandler::class,
        'prompts/list' => PromptListHandler::class,
        'prompts/get' => PromptGetHandler::class,
        'notifications/initialized' => NotificationInitializedHandler::class,
        'notifications/cancelled' => NotificationCancelledHandler::class,
        'ping' => PingHandler::class,
    ];

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * create指定method的process器实例.
     * 每次call都createnewprocess器实例，ensure短生命周期.
     * 注意：call者need手动为return的process器set所需的Manager组件.
     *
     * @return null|MethodHandlerInterface 如果找不到对应method的process器则returnnull
     */
    public function createHandler(string $method): ?MethodHandlerInterface
    {
        if (! $this->hasHandler($method)) {
            return null;
        }

        $handlerClass = self::$methodHandlerMap[$method];

        return new $handlerClass($this->container);
    }

    /**
     * check是否存在指定method的process器.
     */
    public function hasHandler(string $method): bool
    {
        return isset(self::$methodHandlerMap[$method]);
    }

    /**
     * get所有支持的method.
     *
     * @return array<string>
     */
    public function getMethods(): array
    {
        return array_keys(self::$methodHandlerMap);
    }

    /**
     * 注册customizemethodprocess器.
     *
     * @param string $method method名
     * @param class-string<MethodHandlerInterface> $handlerClass process器类名
     */
    public function registerCustomHandler(string $method, string $handlerClass): void
    {
        if (! is_subclass_of($handlerClass, MethodHandlerInterface::class)) {
            throw new InvalidArgumentException(
                "Handler class {$handlerClass} must implement MethodHandlerInterface"
            );
        }

        self::$methodHandlerMap[$method] = $handlerClass;
    }
}
