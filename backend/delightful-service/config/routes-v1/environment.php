<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulEnvironmentApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/environments', static function () {
    // Create environment
    Router::post('', [DelightfulEnvironmentApi::class, 'createDelightfulEnvironment']);
    // Update environment
    Router::put('', [DelightfulEnvironmentApi::class, 'updateDelightfulEnvironment']);
    // Batch get environments
    Router::post('/queries', [DelightfulEnvironmentApi::class, 'getDelightfulEnvironments']);
}, ['middleware' => [RequestContextMiddleware::class]]);
