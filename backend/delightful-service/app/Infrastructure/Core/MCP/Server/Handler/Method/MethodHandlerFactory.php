<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * MCPmethodprocess器factory.
 */
class MethodHandlerFactory
{
    /**
     * methodprocess器mapping,method名 => process器category名.
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
     * createfinger定methodprocess器instance.
     * eachtimecallallcreatenewprocess器instance,ensureshort生命period.
     * notice:call者needhand动forreturnprocess器set所需Managergroupitem.
     *
     * @return null|MethodHandlerInterface if找nottoto应methodprocess器thenreturnnull
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
     * checkwhether存infinger定methodprocess器.
     */
    public function hasHandler(string $method): bool
    {
        return isset(self::$methodHandlerMap[$method]);
    }

    /**
     * get所havesupportmethod.
     *
     * @return array<string>
     */
    public function getMethods(): array
    {
        return array_keys(self::$methodHandlerMap);
    }

    /**
     * registercustomizemethodprocess器.
     *
     * @param string $method method名
     * @param class-string<MethodHandlerInterface> $handlerClass process器category名
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
