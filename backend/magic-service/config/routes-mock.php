<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Interfaces\Mock\AsrApi;
use App\Interfaces\Mock\OpenAIApi;
use App\Interfaces\Mock\SandboxApi;
use Hyperf\HttpServer\Router\Router;

// For unit tests, mock all third-party HTTP calls to improve speed and stability.
Router::addServer('mock-http-service', static function () {
    // LLM calls similar to OpenAI
    Router::addRoute(['POST'], '/v1/chat/completions', [OpenAIApi::class, 'chatCompletion']);
    Router::addRoute(['POST'], '/v1/completions', [OpenAIApi::class, 'chatCompletion']);
    Router::addRoute(['POST'], '/v1/embeddings', [OpenAIApi::class, 'embeddings']);

    // In odin, the DouBao LLM API version path is api/v3
    Router::addRoute(['POST'], '/api/v3/chat/completions', [OpenAIApi::class, 'chatCompletion']);
    Router::addRoute(['POST'], '/api/v3/completions', [OpenAIApi::class, 'chatCompletion']);
    Router::addRoute(['POST'], '/api/v3/embeddings', [OpenAIApi::class, 'embeddings']);

    // Sandbox management API
    Router::addRoute(['GET'], '/api/v1/sandboxes/{sandboxId}', [SandboxApi::class, 'getSandboxStatus']);
    Router::addRoute(['POST'], '/api/v1/sandboxes', [SandboxApi::class, 'createSandbox']);

    // Sandbox workspace status API (via proxy path)
    Router::addRoute(['GET'], '/api/v1/sandboxes/{sandboxId}/proxy/api/v1/workspace/status', [SandboxApi::class, 'getWorkspaceStatus']);

    // Sandbox Agent API (via proxy path)
    Router::addRoute(['POST'], '/api/v1/sandboxes/{sandboxId}/proxy/api/v1/messages/chat', [SandboxApi::class, 'initAgent']);

    // Sandbox ASR task API (via proxy path)
    Router::addRoute(['POST'], '/api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/start', [AsrApi::class, 'startTask']);
    Router::addRoute(['POST'], '/api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/finish', [AsrApi::class, 'finishTask']);
    Router::addRoute(['POST'], '/api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/cancel', [AsrApi::class, 'cancelTask']);
});
