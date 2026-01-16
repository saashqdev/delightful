<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use Delightful\BeDelightful\Infrastructure\Utils\Middleware\SandboxTokenAuthMiddleware;
use Delightful\BeDelightful\Interfaces\Agent\Facade\Sandbox\BeDelightfulAgentSandboxApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\InternalApi\FileApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\OpenApi\OpenProjectApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\OpenApi\OpenTaskApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\SandboxApi;
use Hyperf\HttpServer\Router\Router;

// Sandbox open API - naming is not standardized, needs to be deprecated
Router::addGroup('/api/v1/sandbox-openapi', static function () {
    Router::addGroup('/agents', static function () {
        Router::get('/{code}', [BeDelightfulAgentSandboxApi::class, 'show']);
        Router::post('/tool-execute', [BeDelightfulAgentSandboxApi::class, 'executeTool']);
    });
});

// Sandbox internal API route group - specifically for sandbox to call Super Magic, naming is not standardized, needs to be deprecated
Router::addGroup(
    '/open/internal-api',
    static function () {
        // Super assistant related
        Router::addGroup('/be-agent', static function () {
            // File management related
            Router::addGroup('/file', static function () {
                // Create file version
                Router::post('/versions', [FileApi::class, 'createFileVersion']);
            });
        });
    },
    ['middleware' => [SandboxTokenAuthMiddleware::class]]
);

// Sandbox internal API route group - specifically for sandbox to call Super Magic
Router::addGroup(
    '/api/v1/open-api/sandbox',
    static function () {
        // File management related
        Router::addGroup('/file', static function () {
            // Create file version
            Router::post('/versions', [FileApi::class, 'createFileVersion']);
        });
    },
    ['middleware' => [SandboxTokenAuthMiddleware::class]]
);

// Sandbox open API
Router::addGroup('/api/v1/open-api/sandbox', static function () {
    Router::addGroup('/agents', static function () {
        Router::get('/{code}', [BeDelightfulAgentSandboxApi::class, 'show']);
        Router::post('/tool-execute', [BeDelightfulAgentSandboxApi::class, 'executeTool']);
    });
});

// Project related - public API
Router::addGroup('/api/v1/open-api/be-delightful/projects', static function () {
    // Get project basic information (project name, etc.) - no login required
    Router::get('/{id}', [OpenProjectApi::class, 'show']);
});

// be-delightful open API, note: subsequent open APIs all use be-delightful, not be-agent
Router::addGroup(
    '/api/v1/open-api/be-delightful',
    static function () {
        Router::post('/sandbox/init', [SandboxApi::class, 'initSandboxByApiKey']);
        // Create agent task
        Router::post('/agent-task', [OpenTaskApi::class, 'agentTask']);

        // Execute script task, temporarily not supported
        // Router::post('/script-task', [OpenTaskApi::class, 'scriptTask']);

        // Update task status
        Router::put('/task/status', [OpenTaskApi::class, 'updateTaskStatus']);

        // // Get task
        Router::get('/task', [OpenTaskApi::class, 'getTask']);
        // // Get task list
        // Router::get('/tasks', [OpenTaskApi::class, 'getOpenApiTaskList']);

        // Task related
        Router::addGroup('/task', static function () {
            // Get task attachment list
            Router::get('/attachments', [OpenTaskApi::class, 'getOpenApiTaskAttachments']);
        });
    },
    ['middleware' => [RequestContextMiddleware::class]]
);
