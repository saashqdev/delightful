<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Kernel\Facade\GlobalConfigApi;
// Platform settings routes moved to admin.php
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/settings', static function () {
    // Get global configuration
    Router::get('/global', [GlobalConfigApi::class, 'getGlobalConfig']);
    Router::put('/global', [GlobalConfigApi::class, 'updateGlobalConfig'], ['middleware' => [RequestContextMiddleware::class]]);
});
