<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Admin\Facade\Agent\AdminAgentApi;
use App\Interfaces\Admin\Facade\Agent\AgentGlobalSettingsApi;
use App\Interfaces\Kernel\Facade\PlatformSettingsApi;
use App\Interfaces\OrganizationEnvironment\Facade\Admin\OrganizationApi;
use App\Interfaces\Permission\Facade\OrganizationAdminApi;
use App\Interfaces\Permission\Facade\PermissionApi;
use App\Interfaces\Permission\Facade\RoleApi;
use App\Interfaces\Provider\Facade\AiAbilityApi;
use App\Interfaces\Provider\Facade\Open\ServiceProviderOpenApi;
use App\Interfaces\Provider\Facade\ServiceProviderApi;
use Hyperf\HttpServer\Router\Router;

// Routes that skip admin authorization
Router::addGroup('/api/v1', static function () {
    Router::addGroup('/service-providers', static function () {
        // Fetch providers by category (no admin auth)
        Router::post('/category', [ServiceProviderOpenApi::class, 'getProvidersByCategory']);
        Router::post('/by-category', [ServiceProviderOpenApi::class, 'getProvidersByCategory']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);

// Organization admin routes
Router::addGroup('/api/v1/admin', static function () {
    Router::addGroup('/service-providers', static function () {
        // Service provider management
        Router::get('', [ServiceProviderApi::class, 'getServiceProviders']);
        Router::get('/{serviceProviderConfigId:\d+}', [ServiceProviderApi::class, 'getServiceProviderConfigModels']);
        Router::put('', [ServiceProviderApi::class, 'updateServiceProviderConfig']);
        Router::post('', [ServiceProviderApi::class, 'addServiceProviderForOrganization']);
        Router::delete('/{serviceProviderConfigId:\d+}', [ServiceProviderApi::class, 'deleteServiceProviderForOrganization']);

        // Model management
        Router::post('/models', [ServiceProviderApi::class, 'saveModelToServiceProvider']);
        Router::delete('/models/{modelId}', [ServiceProviderApi::class, 'deleteModel']);
        Router::put('/models/{modelId}/status', [ServiceProviderApi::class, 'updateModelStatus']);
        Router::post('/models/queries', [ServiceProviderApi::class, 'queriesModels']); // Get models by type and status

        // Model identifier management
        Router::post('/model-id', [ServiceProviderApi::class, 'addModelIdForOrganization']);
        Router::delete('/model-ids/{modelId}', [ServiceProviderApi::class, 'deleteModelIdForOrganization']);

        // Original model management
        Router::get('/original-models', [ServiceProviderApi::class, 'listOriginalModels']);
        Router::post('/original-models', [ServiceProviderApi::class, 'addOriginalModel']);

        // Other operations
        Router::post('/connectivity-test', [ServiceProviderApi::class, 'connectivityTest']);
        Router::post('/by-category', [ServiceProviderApi::class, 'getOrganizationProvidersByCategory']);
        Router::get('/non-official-llm', [ServiceProviderApi::class, 'getNonOfficialLlmProviders']);
        Router::get('/available-llm', [ServiceProviderApi::class, 'getAllAvailableLlmProviders']);
        Router::get('/office-info', [ServiceProviderApi::class, 'isCurrentOrganizationOfficial']);
    }, ['middleware' => [RequestContextMiddleware::class]]);

    // AI capability management
    Router::addGroup('/ai-abilities', static function () {
        Router::get('', [AiAbilityApi::class, 'queries']);
        Router::get('/{code}', [AiAbilityApi::class, 'detail']);
        Router::put('/{code}', [AiAbilityApi::class, 'update']);
    }, ['middleware' => [RequestContextMiddleware::class]]);

    Router::addGroup('/globals', static function () {
        Router::addGroup('/agents', static function () {
            Router::put('/settings', [AgentGlobalSettingsApi::class, 'updateGlobalSettings']);
            Router::get('/settings', [AgentGlobalSettingsApi::class, 'getGlobalSettings']);
        });
    }, ['middleware' => [RequestContextMiddleware::class]]);

    Router::addGroup('/agents', static function () {
        Router::get('/published', [AdminAgentApi::class, 'getPublishedAgents']);
        Router::post('/queries', [AdminAgentApi::class, 'queriesAgents']);
        Router::get('/creators', [AdminAgentApi::class, 'getOrganizationAgentsCreators']);
        Router::get('/{agentId}', [AdminAgentApi::class, 'getAgentDetail']);
        Router::delete('/{agentId}', [AdminAgentApi::class, 'deleteAgent']);
    }, ['middleware' => [RequestContextMiddleware::class]]);

    // Organization administrators
    Router::addGroup('/organization-admin', static function () {
        Router::get('/list', [OrganizationAdminApi::class, 'list']);
        Router::get('/{id:\d+}', [OrganizationAdminApi::class, 'show']);
        Router::delete('/{id:\d+}', [OrganizationAdminApi::class, 'destroy']);
        Router::post('/grant', [OrganizationAdminApi::class, 'grant']);
        Router::post('/transfer-owner', [OrganizationAdminApi::class, 'transferOwner']);
    }, ['middleware' => [RequestContextMiddleware::class]]);

    // Role permissions (permission tree)
    Router::addGroup('/roles', static function () {
        Router::get('/permissions/tree', [PermissionApi::class, 'getPermissionTree']);
        Router::get('/sub-admins', [RoleApi::class, 'getSubAdminList']);
        Router::post('/sub-admins', [RoleApi::class, 'createSubAdmin']);
        Router::put('/sub-admins/{id}', [RoleApi::class, 'updateSubAdmin']);
        Router::delete('/sub-admins/{id}', [RoleApi::class, 'deleteSubAdmin']);
        Router::get('/sub-admins/{id}', [RoleApi::class, 'getSubAdminById']);
    }, ['middleware' => [RequestContextMiddleware::class]]);

    // Organization list
    Router::addGroup('/organizations', static function () {
        Router::get('', [OrganizationApi::class, 'queries']);
    }, ['middleware' => [RequestContextMiddleware::class]]);
});

// Platform settings (admin)
Router::addGroup('/api/v1/platform', static function () {
    Router::get('/setting', [PlatformSettingsApi::class, 'show']);
    Router::put('/setting', [PlatformSettingsApi::class, 'update']);
}, ['middleware' => [RequestContextMiddleware::class]]);
