<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulUserTaskApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/user', static function () {
    // 获取任务列表
    Router::get('/task', [DelightfulUserTaskApi::class, 'listTask']);
    // 创建任务
    Router::post('/task', [DelightfulUserTaskApi::class, 'createTask']);
    // 获取单个任务
    Router::get('/task/{id}', [DelightfulUserTaskApi::class, 'getTask']);
    // 更新任务
    Router::put('/task/{id}', [DelightfulUserTaskApi::class, 'updateTask']);
    // 删除任务
    Router::delete('/task/{id}', [DelightfulUserTaskApi::class, 'deleteTask']);
}, ['middleware' => [RequestContextMiddleware::class]]);
