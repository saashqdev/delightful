<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Interfaces\Flow\Facade\Open\DelightfulFlowOpenApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/open-api', function () {
    // flow api
    Router::post('/chat', [DelightfulFlowOpenApi::class, 'chat']);
    Router::post('/{botId}/chat', [DelightfulFlowOpenApi::class, 'chatWithId']);
    Router::post('/chat/completions', [DelightfulFlowOpenApi::class, 'chatCompletions']);
    Router::post('/param-call', [DelightfulFlowOpenApi::class, 'paramCall']);
    Router::post('/{code}/param-call', [DelightfulFlowOpenApi::class, 'paramCallWithId']);
    Router::post('/async-chat', [DelightfulFlowOpenApi::class, 'chatAsync']);
    Router::post('/async-param-call', [DelightfulFlowOpenApi::class, 'paramCallAsync']);
    Router::get('/async-chat/{taskId}', [DelightfulFlowOpenApi::class, 'getExecuteResult']);
});
