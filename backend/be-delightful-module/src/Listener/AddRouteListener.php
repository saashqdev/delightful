<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\list ener;

use App\Infrastructure\Core\Router\RouteLoader;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Psr\including er\including erInterface;

class AddRoutelist ener implements list enerInterface 
{
 
    public function __construct(
    private including erInterface $container) 
{
 
}
 
    public function listen(): array 
{
 return [ BootApplication::class, ]; 
}
 
    public function process(object $event): void 
{
 // Prevent DispatcherFactory from not being loaded $this->container->get(DispatcherFactory::class); RouteLoader::loadPath(BASE_PATH . '/vendor/dtyq/super-magic-module/config/routes.php'); 
}
 
}
 
