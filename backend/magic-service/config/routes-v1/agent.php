<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Agent\Facade\MagicAgentApi;
use App\Interfaces\Agent\Facade\MagicBotThirdPlatformChatApi;
use App\Interfaces\Agent\Facade\Open\ThirdPlatformChatApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    // Agent management
    Router::addGroup('/agents', static function () {
        // List agents
        Router::get('/queries', [MagicAgentApi::class, 'queries']);
        Router::post('/queries', [MagicAgentApi::class, 'queries']);
        // Get available agents
        Router::get('/available', [MagicAgentApi::class, 'queriesAvailable']);
        // Get agents available for chat mode
        Router::get('/chat-mode/available', [MagicAgentApi::class, 'getChatModeAvailableAgents']);
        // Save an agent
        Router::post('', [MagicAgentApi::class, 'saveAgent']);
        // Get a single agent
        Router::get('/{agentId:\d+}', [MagicAgentApi::class, 'getAgentDetailByAgentId']);
        // Delete an agent
        Router::delete('/{agentId}', [MagicAgentApi::class, 'deleteAgentById']);

        // Update agent status
        Router::put('/{agentId}/status', [MagicAgentApi::class, 'updateAgentStatus']);
        // Update enterprise agent status
        Router::put('/{agentId}/enterprise-status', [MagicAgentApi::class, 'updateAgentEnterpriseStatus']);
        // Register agent and add friend
        Router::post('/{agentVersionId}/register-friend', [MagicAgentApi::class, 'registerAgentAndAddFriend']);
        // Check whether the agent has been updated
        Router::get('/{agentId}/is-updated', [MagicAgentApi::class, 'isUpdated']);

        // Save an instruction
        Router::post('/{agentId}/instructs', [MagicAgentApi::class, 'saveInstruct']);

        // Get the max version
        Router::get('/{agentId}/max', [MagicAgentApi::class, 'getAgentMaxVersion']);

        // Version management
        Router::addGroup('/versions', static function () {
            // Get a released version
            Router::get('/{agentVersionId:\d+}', [MagicAgentApi::class, 'getAgentVersionById']);
            // Get agents belonging to the organization
            Router::get('/organization', [MagicAgentApi::class, 'getAgentsByOrganization']);
            // Get marketplace agents
            Router::get('/marketplace', [MagicAgentApi::class, 'getAgentsFromMarketplace']);
            // Release a version
            Router::post('', [MagicAgentApi::class, 'releaseAgentVersion']);
        });

        // Get versions for a specific agent
        Router::get('/{agentId:\d+}/versions', [MagicAgentApi::class, 'getReleaseAgentVersions']);

        // Version operations
        Router::addGroup('/versions', static function () {
            // Get details by userId
            Router::get('/{userId}/user', [MagicAgentApi::class, 'getDetailByUserId']);
        });
    });

    // Instruction options
    Router::addGroup('/agent-options', static function () {
        // Get instruction type options
        Router::get('/instruct-types', [MagicAgentApi::class, 'getInstructTypeOptions']);
        // Get instruction group type options
        Router::get('/instruct-group-types', [MagicAgentApi::class, 'getInstructGroupTypeOptions']);
        // Get instruction state color options
        Router::get('/instruct-state-colors', [MagicAgentApi::class, 'getInstructionStateColorOptions']);
        // Get instruction icon color options
        Router::get('/instruct-state-icons', [MagicAgentApi::class, 'getInstructionIconColorOptions']);
        // Get system instruction type options
        Router::get('/instruct-system', [MagicAgentApi::class, 'getSystemInstructTypeOptions']);
    });

    // Third-party bot chat management
    Router::addGroup('/agents/third-platform', function () {
        // Save
        Router::post('/', [MagicBotThirdPlatformChatApi::class, 'save']);
        // Query
        Router::post('/{botId}/queries', [MagicBotThirdPlatformChatApi::class, 'queries']);
        // List
        Router::get('/{botId}/list', [MagicBotThirdPlatformChatApi::class, 'listByBotId']);
        // Delete
        Router::delete('/{id}', [MagicBotThirdPlatformChatApi::class, 'destroy']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);

// Third-party bot chat entry point
Router::addGroup('/api/v1/bot/third-platform', function () {
    // Chat
    Router::addRoute(['GET', 'POST'], '/chat', [ThirdPlatformChatApi::class, 'chat']);
});
