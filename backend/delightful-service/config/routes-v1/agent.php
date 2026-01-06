<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Agent\Facade\DelightfulAgentApi;
use App\Interfaces\Agent\Facade\DelightfulBotThirdPlatformChatApi;
use App\Interfaces\Agent\Facade\Open\ThirdPlatformChatApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    // Agent management
    Router::addGroup('/agents', static function () {
        // List agents
        Router::get('/queries', [DelightfulAgentApi::class, 'queries']);
        Router::post('/queries', [DelightfulAgentApi::class, 'queries']);
        // Get available agents
        Router::get('/available', [DelightfulAgentApi::class, 'queriesAvailable']);
        // Get agents available for chat mode
        Router::get('/chat-mode/available', [DelightfulAgentApi::class, 'getChatModeAvailableAgents']);
        // Save an agent
        Router::post('', [DelightfulAgentApi::class, 'saveAgent']);
        // Get a single agent
        Router::get('/{agentId:\d+}', [DelightfulAgentApi::class, 'getAgentDetailByAgentId']);
        // Delete an agent
        Router::delete('/{agentId}', [DelightfulAgentApi::class, 'deleteAgentById']);

        // Update agent status
        Router::put('/{agentId}/status', [DelightfulAgentApi::class, 'updateAgentStatus']);
        // Update enterprise agent status
        Router::put('/{agentId}/enterprise-status', [DelightfulAgentApi::class, 'updateAgentEnterpriseStatus']);
        // Register agent and add friend
        Router::post('/{agentVersionId}/register-friend', [DelightfulAgentApi::class, 'registerAgentAndAddFriend']);
        // Check whether the agent has been updated
        Router::get('/{agentId}/is-updated', [DelightfulAgentApi::class, 'isUpdated']);

        // Save an instruction
        Router::post('/{agentId}/instructs', [DelightfulAgentApi::class, 'saveInstruct']);

        // Get the max version
        Router::get('/{agentId}/max', [DelightfulAgentApi::class, 'getAgentMaxVersion']);

        // Version management
        Router::addGroup('/versions', static function () {
            // Get a released version
            Router::get('/{agentVersionId:\d+}', [DelightfulAgentApi::class, 'getAgentVersionById']);
            // Get agents belonging to the organization
            Router::get('/organization', [DelightfulAgentApi::class, 'getAgentsByOrganization']);
            // Get marketplace agents
            Router::get('/marketplace', [DelightfulAgentApi::class, 'getAgentsFromMarketplace']);
            // Release a version
            Router::post('', [DelightfulAgentApi::class, 'releaseAgentVersion']);
        });

        // Get versions for a specific agent
        Router::get('/{agentId:\d+}/versions', [DelightfulAgentApi::class, 'getReleaseAgentVersions']);

        // Version operations
        Router::addGroup('/versions', static function () {
            // Get details by userId
            Router::get('/{userId}/user', [DelightfulAgentApi::class, 'getDetailByUserId']);
        });
    });

    // Instruction options
    Router::addGroup('/agent-options', static function () {
        // Get instruction type options
        Router::get('/instruct-types', [DelightfulAgentApi::class, 'getInstructTypeOptions']);
        // Get instruction group type options
        Router::get('/instruct-group-types', [DelightfulAgentApi::class, 'getInstructGroupTypeOptions']);
        // Get instruction state color options
        Router::get('/instruct-state-colors', [DelightfulAgentApi::class, 'getInstructionStateColorOptions']);
        // Get instruction icon color options
        Router::get('/instruct-state-icons', [DelightfulAgentApi::class, 'getInstructionIconColorOptions']);
        // Get system instruction type options
        Router::get('/instruct-system', [DelightfulAgentApi::class, 'getSystemInstructTypeOptions']);
    });

    // Third-party bot chat management
    Router::addGroup('/agents/third-platform', function () {
        // Save
        Router::post('/', [DelightfulBotThirdPlatformChatApi::class, 'save']);
        // Query
        Router::post('/{botId}/queries', [DelightfulBotThirdPlatformChatApi::class, 'queries']);
        // List
        Router::get('/{botId}/list', [DelightfulBotThirdPlatformChatApi::class, 'listByBotId']);
        // Delete
        Router::delete('/{id}', [DelightfulBotThirdPlatformChatApi::class, 'destroy']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);

// Third-party bot chat entry point
Router::addGroup('/api/v1/bot/third-platform', function () {
    // Chat
    Router::addRoute(['GET', 'POST'], '/chat', [ThirdPlatformChatApi::class, 'chat']);
});
