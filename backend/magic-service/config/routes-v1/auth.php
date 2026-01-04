<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use App\Interfaces\Authentication\Facade\AuthenticationApi;
use App\Interfaces\Authentication\Facade\LoginApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    // Authentication routes (RESTful style)
    Router::addGroup('/auth', static function () {
        // Login check - GET to verify authentication status
        Router::get('/status', [AuthenticationApi::class, 'authCheck']);

        // Environment info - GET to fetch resource
        Router::get('/environment', [AuthenticationApi::class, 'authEnvironment']);
    });

    // Session management (RESTful style)
    Router::addGroup('/sessions', static function () {
        // Create session (login)
        Router::post('', [LoginApi::class, 'login']);
        // Destroy session (logout) - add if needed
        // Router::delete('', [LoginApi::class, 'logout']);
    });
});
