<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\Router\RouteLoader;
use Hyperf\HttpServer\Router\Router;

// Basic routes
Router::get('/', function () {
    return 'hello, delightful-service!';
});
Router::get('/favicon.ico', function () {
    return '';
});
Router::addRoute(
    ['GET', 'POST', 'HEAD', 'OPTIONS'],
    '/heartbeat',
    function () {
        return ['status' => 'UP'];
    }
);

// Load mock routes (for testing)
require BASE_PATH . '/config/routes-mock.php';

// Load v1 routes
RouteLoader::loadDir(BASE_PATH . '/config/routes-v1');
