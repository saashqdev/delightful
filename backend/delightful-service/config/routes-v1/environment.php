<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulEnvironmentApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/environments', static function () {
    // 创建环境
    Router::post('', [DelightfulEnvironmentApi::class, 'createDelightfulEnvironment']);
    // 更新环境
    Router::put('', [DelightfulEnvironmentApi::class, 'updateDelightfulEnvironment']);
    // 批量获取环境
    Router::post('/queries', [DelightfulEnvironmentApi::class, 'getDelightfulEnvironments']);
}, ['middleware' => [RequestContextMiddleware::class]]);
