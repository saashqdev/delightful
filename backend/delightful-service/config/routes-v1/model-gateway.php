<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\ModelGateway\Facade\Open\OpenAIProxyApi;
use App\Interfaces\Provider\Facade\ServiceProviderApi;
use Hyperf\HttpServer\Router\Router;

// OpenAI compatible interface - must be openai mode, do not modify this
Router::addGroup('/v1', function () {
    Router::post('/chat/completions', [OpenAIProxyApi::class, 'chatCompletions']);
    Router::post('/embeddings', [OpenAIProxyApi::class, 'embeddings']);
    Router::get('/models', [OpenAIProxyApi::class, 'models']);
    Router::post('/images/generations', [OpenAIProxyApi::class, 'textGenerateImage']);
    Router::post('/images/edits', [OpenAIProxyApi::class, 'imageEdit']);
    // @deprecated Use /v2/search instead - supports multiple search engines
    Router::get('/search', [OpenAIProxyApi::class, 'bingSearch']);
});

Router::addGroup('/v2', function () {
    Router::post('/images/generations', [OpenAIProxyApi::class, 'textGenerateImageV2']);
    Router::post('/images/edits', [OpenAIProxyApi::class, 'imageEditV2']);
    // Unified search endpoint - supports multiple search engines (bing, google, tavily, duckduckgo, jina)
    Router::get('/search', [OpenAIProxyApi::class, 'unifiedSearch']);
});

// Frontend model interface
Router::addGroup('/api/v1', static function () {
    // Super Delightful display models
    Router::get('/be-delightful-models', [ServiceProviderApi::class, 'getBeDelightfulDisplayModels']);
}, ['middleware' => [RequestContextMiddleware::class]]);
