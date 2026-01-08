<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulUserTaskApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/user', static function () {
    // Get task list
    Router::get('/task', [DelightfulUserTaskApi::class, 'listTask']);
    // Create task
    Router::post('/task', [DelightfulUserTaskApi::class, 'createTask']);
    // Get single task
    Router::get('/task/{id}', [DelightfulUserTaskApi::class, 'getTask']);
    // Update task
    Router::put('/task/{id}', [DelightfulUserTaskApi::class, 'updateTask']);
    // Delete task
    Router::delete('/task/{id}', [DelightfulUserTaskApi::class, 'deleteTask']);
}, ['middleware' => [RequestContextMiddleware::class]]);
