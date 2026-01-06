<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Delightful\BeDelightful\Infrastructure\Utils\Middleware\RequestContextMiddlewareV2;
use Delightful\BeDelightful\Interfaces\Share\Facade\ShareApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup(
    '/api/v1/share',
    static function () {
        // Share management
        Router::addGroup('/resources', static function () {
            // Create resource share
            Router::post('/create', [ShareApi::class, 'createShare']);
            // Update share settings
            Router::post('/{id}/update', [ShareApi::class, 'updateShare']);
            // Cancel share
            Router::post('/{id}/cancel', [ShareApi::class, 'cancelShareByResourceId']);

            // Get user share resource list
            Router::post('/list', [ShareApi::class, 'getShareList']);
            // Get share information by share code
            Router::get('/{shareCode}/setting', [ShareApi::class, 'getShareByCode']);
        });

        // Access shared content
        Router::addGroup('/access', static function () {
            // Access share link
            Router::post('/{shareCode}', [ShareApi::class, 'accessShare']);
        });
    },
    ['middleware' => [RequestContextMiddlewareV2::class]]
);

Router::addGroup('/api/v1/share', static function () {
    // Check if password is required
    Router::post('/resources/{shareCode}/check', [ShareApi::class, 'checkShare']);
    // Get share details
    Router::post('/resources/{shareCode}/detail', [ShareApi::class, 'getShareDetail']);
});
