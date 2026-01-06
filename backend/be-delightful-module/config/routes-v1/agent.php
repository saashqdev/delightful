<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Delightful\SuperDelightful\Interfaces\Agent\Facade\Admin\SuperDelightfulAgentAdminApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/super-magic', function () {
    Router::addGroup('/agents', function () {
        Router::get('/builtin-tools', [SuperDelightfulAgentAdminApi::class, 'tools']);

        Router::post('', [SuperDelightfulAgentAdminApi::class, 'save']);
        Router::post('/queries', [SuperDelightfulAgentAdminApi::class, 'queries']);
        Router::post('/ai-optimize', [SuperDelightfulAgentAdminApi::class, 'aiOptimize']);
        Router::get('/{code}', [SuperDelightfulAgentAdminApi::class, 'show']);
        Router::delete('/{code}', [SuperDelightfulAgentAdminApi::class, 'destroy']);
        Router::put('/{code}/enable', [SuperDelightfulAgentAdminApi::class, 'enable']);
        Router::put('/{code}/disable', [SuperDelightfulAgentAdminApi::class, 'disable']);
        Router::post('/order', [SuperDelightfulAgentAdminApi::class, 'saveOrder']);
    });
});
