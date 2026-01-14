<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Interfaces\MCP\Facade\Admin\MCPOAuth2BindingApi;
use App\Interfaces\MCP\Facade\Admin\MCPServerAdminApi;
use App\Interfaces\MCP\Facade\Admin\MCPServerToolAdminApi;
use App\Interfaces\MCP\Facade\Admin\MCPBeDelightfulProjectSettingAdminApi;
use App\Interfaces\MCP\Facade\Admin\MCPUserSettingAdminApi;
use BeDelightful\PhpMcp\Server\Framework\Hyperf\HyperfMcpServer;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/mcp', function () {
    Router::addGroup('/server', function () {
        Router::post('', [MCPServerAdminApi::class, 'save']);
        Router::post('/queries', [MCPServerAdminApi::class, 'queries']);
        Router::get('/{code}/status', [MCPServerAdminApi::class, 'checkStatus']);
        Router::put('/{code}/status', [MCPServerAdminApi::class, 'updateStatus']);
        Router::get('/{code}', [MCPServerAdminApi::class, 'show']);
        Router::delete('/{code}', [MCPServerAdminApi::class, 'destroy']);

        Router::post('/{code}/tools', [MCPServerToolAdminApi::class, 'queries']);
        Router::post('/{code}/tool', [MCPServerToolAdminApi::class, 'save']);
        Router::get('/{code}/tool/{id}', [MCPServerToolAdminApi::class, 'show']);
        Router::delete('/{code}/tool/{id}', [MCPServerToolAdminApi::class, 'destroy']);
    });

    Router::addGroup('/be-delightful', function () {
        Router::put('/project/{projectId}/setting', [MCPBeDelightfulProjectSettingAdminApi::class, 'save']);
        Router::get('/project/{projectId}/setting', [MCPBeDelightfulProjectSettingAdminApi::class, 'get']);
    });

    Router::post('/available/queries', [MCPServerAdminApi::class, 'availableQueries']);

    Router::post('/user-setting/{code}/require-fields', [MCPUserSettingAdminApi::class, 'saveRequiredFields']);
    Router::get('/user-setting/{code}', [MCPUserSettingAdminApi::class, 'getUserSettings']);

    // OAuth2 binding and unbinding routes
    Router::addGroup('/oauth2', function () {
        Router::post('/bind', [MCPOAuth2BindingApi::class, 'bind']);
        Router::post('/unbind', [MCPOAuth2BindingApi::class, 'unbind']);
    });

    Router::addGroup('/sse', function () {
        Router::addRoute(['POST', 'GET', 'DELETE'], '/{code}', function (string $code) {
            return di(HyperfMcpServer::class)->handle('DelightfulMcp-' . $code, '1.0.0', true);
        });
    });
});
