<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Listener;

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
        // avoid DispatcherFactory not being loaded
        $this->container->get(DispatcherFactory::class);
        RouteLoader::loadPath(BASE_PATH . '/vendor/dtyq/super-delightful-module/config/routes.php');
    }
}
