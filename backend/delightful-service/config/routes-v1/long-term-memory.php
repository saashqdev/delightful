<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\LongTermMemory\Facade\LongTermMemoryAdminApi;
use Hyperf\HttpServer\Router\Router;

// Long-term memory collection operation API routes
Router::addGroup('/api/v1/memories', static function () {
    // Basic operations
    Router::post('/queries', [LongTermMemoryAdminApi::class, 'getMemoryList']);

    // Batch process memory suggestions (accept/reject)
    Router::put('/status', [LongTermMemoryAdminApi::class, 'batchProcessMemorySuggestions']);

    // Batch update memory enabled status (enable/disable)
    Router::put('/enabled', [LongTermMemoryAdminApi::class, 'batchUpdateMemoryStatus']);

    // Memory statistics
    Router::get('/stats', [LongTermMemoryAdminApi::class, 'getMemoryStats']);

    // System prompt
    Router::get('/prompt', [LongTermMemoryAdminApi::class, 'getMemoryPrompt']);

    // Evaluate conversation content and possibly create memory
    Router::post('/evaluate', [LongTermMemoryAdminApi::class, 'evaluateConversation']);
}, ['middleware' => [RequestContextMiddleware::class]]);

// Single memory operation API routes
Router::addGroup('/api/v1/memory', static function () {
    // Basic CRUD operations
    Router::get('/{memoryId}', [LongTermMemoryAdminApi::class, 'getMemory']);
    Router::put('/{memoryId}', [LongTermMemoryAdminApi::class, 'updateMemory']);
    Router::delete('/{memoryId}', [LongTermMemoryAdminApi::class, 'deleteMemory']);
    Router::post('', [LongTermMemoryAdminApi::class, 'createMemory']);
    // Memory reinforcement
    Router::post('/{memoryId}/reinforce', [LongTermMemoryAdminApi::class, 'reinforceMemory']);
}, ['middleware' => [RequestContextMiddleware::class]]);
