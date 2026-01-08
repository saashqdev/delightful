<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\KnowledgeBase\Facade\KnowledgeBaseApi;
use App\Interfaces\KnowledgeBase\Facade\KnowledgeBaseDocumentApi;
use App\Interfaces\KnowledgeBase\Facade\KnowledgeBaseFragmentApi;
use App\Interfaces\KnowledgeBase\Facade\KnowledgeBaseProviderApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/knowledge-bases', static function () {
    // Knowledge base
    Router::addGroup('', function () {
        Router::post('', [KnowledgeBaseApi::class, 'create']);
        Router::put('/{code}', [KnowledgeBaseApi::class, 'update']);
        Router::post('/queries', [KnowledgeBaseApi::class, 'queries']);
        Router::get('/{code}', [KnowledgeBaseApi::class, 'show']);
        Router::delete('/{code}', [KnowledgeBaseApi::class, 'destroy']);
    });

    // Document
    Router::addGroup('/{knowledgeBaseCode}/documents', function () {
        Router::post('', [KnowledgeBaseDocumentApi::class, 'create']);
        Router::put('/{code}', [KnowledgeBaseDocumentApi::class, 'update']);
        Router::post('/queries', [KnowledgeBaseDocumentApi::class, 'queries']);
        Router::get('/{code}', [KnowledgeBaseDocumentApi::class, 'show']);
        Router::delete('/{code}', [KnowledgeBaseDocumentApi::class, 'destroy']);
        Router::post('/{code}/re-vectorized', [KnowledgeBaseDocumentApi::class, 'reVectorized']);
    });

    // Fragment
    Router::addGroup('/{knowledgeBaseCode}/documents/{documentCode}/fragments', function () {
        Router::post('', [KnowledgeBaseFragmentApi::class, 'create']);
        Router::put('/{id}', [KnowledgeBaseFragmentApi::class, 'update']);
        Router::post('/queries', [KnowledgeBaseFragmentApi::class, 'queries']);
        Router::get('/{id}', [KnowledgeBaseFragmentApi::class, 'show']);
        Router::delete('/{id}', [KnowledgeBaseFragmentApi::class, 'destroy']);
    });
    Router::post('/fragments/preview', [KnowledgeBaseFragmentApi::class, 'fragmentPreview']);
    Router::post('/{code}/fragments/similarity', [KnowledgeBaseFragmentApi::class, 'similarity']);

    // Model provider
    Router::addGroup('/providers', function () {
        Router::get('/rerank/list', [KnowledgeBaseProviderApi::class, 'getOfficialRerankProviderList']);
        Router::get('/embedding/list', [KnowledgeBaseProviderApi::class, 'getEmbeddingProviderList']);
    });

    // File
    Router::addGroup('/files', function () {
        // Get file link
        Router::get('/link', [KnowledgeBaseApi::class, 'getFileLink']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
