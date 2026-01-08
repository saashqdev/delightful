<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Interfaces\Authentication\Facade\Admin\ApiKeyProviderAdminApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/authentication', function () {
    // API key management
    Router::addGroup('/api-key', function () {
        Router::post('', [ApiKeyProviderAdminApi::class, 'save']);
        Router::post('/queries', [ApiKeyProviderAdminApi::class, 'queries']);
        Router::get('/{code}', [ApiKeyProviderAdminApi::class, 'show']);
        Router::delete('/{code}', [ApiKeyProviderAdminApi::class, 'destroy']);
        Router::post('/{code}/rebuild', [ApiKeyProviderAdminApi::class, 'changeSecretKey']);
    });
});
