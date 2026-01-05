<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use Dtyq\SuperMagic\Infrastructure\Utils\Middleware\SandboxTokenAuthMiddleware;
use Dtyq\SuperMagic\Interfaces\Agent\Facade\Sandbox\SuperMagicAgentSandboxApi;
use Dtyq\SuperMagic\Interfaces\SuperAgent\Facade\InternalApi\FileApi;
use Dtyq\SuperMagic\Interfaces\SuperAgent\Facade\OpenApi\OpenProjectApi;
use Dtyq\SuperMagic\Interfaces\SuperAgent\Facade\OpenApi\OpenTaskApi;
use Dtyq\SuperMagic\Interfaces\SuperAgent\Facade\SandboxApi;
use Hyperf\HttpServer\Router\Router;

// Sandbox open interface - naming is non-standard, needs to be deprecated
Router::addGroup('/api/v1/sandbox-openapi', static function () {
    Router::addGroup('/agents', static function () {
        Router::get('/{code}', [SuperMagicAgentSandboxApi::class, 'show']);
        Router::post('/tool-execute', [SuperMagicAgentSandboxApi::class, 'executeTool']);
    });
});

// Sandbox internal API route group - specifically for sandbox calling Super Magic, naming is non-standard, needs to be deprecated
Router::addGroup(
    '/open/internal-api',
    static function () {
        // Super Agent related
        Router::addGroup('/super-agent', static function () {
            // File management related
            Router::addGroup('/file', static function () {
                // Create file version
                Router::post('/versions', [FileApi::class, 'createFileVersion']);
            });
        });
    },
    ['middleware' => [SandboxTokenAuthMiddleware::class]]
);

// Sandbox internal API route group - specifically for sandbox calling Super Magic
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

// Sandbox open interface
Router::addGroup('/api/v1/open-api/sandbox', static function () {
    Router::addGroup('/agents', static function () {
        Router::get('/{code}', [SuperMagicAgentSandboxApi::class, 'show']);
        Router::post('/tool-execute', [SuperMagicAgentSandboxApi::class, 'executeTool']);
    });
});

// Project related - public interface
Router::addGroup('/api/v1/open-api/super-magic/projects', static function () {
    // Get project basic information (project name, etc.) - no login required
    Router::get('/{id}', [OpenProjectApi::class, 'show']);
});

// super-magic open api, note: all subsequent open APIs use super-magic, not super-agent
Router::addGroup(
    '/api/v1/open-api/super-magic',
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
            // Get attachment list under task
            Router::get('/attachments', [OpenTaskApi::class, 'getOpenApiTaskAttachments']);
        });
    },
    ['middleware' => [RequestContextMiddleware::class]]
);
