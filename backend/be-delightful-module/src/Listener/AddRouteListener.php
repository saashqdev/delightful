<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Listener;

use App\Infrastructure\Core\Router\RouteLoader;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Psr\Container\ContainerInterface;

class AddRouteListener implements ListenerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        // 避免 DispatcherFactory 没有被加载
        $this->container->get(DispatcherFactory::class);
        RouteLoader::loadPath(BASE_PATH . '/vendor/dtyq/be-delightful-module/config/routes.php');
    }
}
