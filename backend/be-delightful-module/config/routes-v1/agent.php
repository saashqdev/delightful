<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Delightful\BeDelightful\Interfaces\Agent\Facade\Admin\BeDelightfulAgentAdminApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/be-delightful', function () {
    Router::addGroup('/agents', function () {
        Router::get('/builtin-tools', [BeDelightfulAgentAdminApi::class, 'tools']);

        Router::post('', [BeDelightfulAgentAdminApi::class, 'save']);
        Router::post('/queries', [BeDelightfulAgentAdminApi::class, 'queries']);
        Router::post('/ai-optimize', [BeDelightfulAgentAdminApi::class, 'aiOptimize']);
        Router::get('/{code}', [BeDelightfulAgentAdminApi::class, 'show']);
        Router::delete('/{code}', [BeDelightfulAgentAdminApi::class, 'destroy']);
        Router::put('/{code}/enable', [BeDelightfulAgentAdminApi::class, 'enable']);
        Router::put('/{code}/disable', [BeDelightfulAgentAdminApi::class, 'disable']);
        Router::post('/order', [BeDelightfulAgentAdminApi::class, 'saveOrder']);
    });
});
