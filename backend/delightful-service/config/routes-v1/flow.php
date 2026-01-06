<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowAIModelFlowAdminApi;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowApiKeyFlowAdminApi;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowDraftFlowAdminApi;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowFlowAdminApi;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowToolSetApiFlow;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowTriggerTestcaseFlowAdminApi;
use App\Interfaces\Flow\Facade\Admin\DelightfulFlowVersionFlowAdminApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    Router::addGroup('/flows', static function () {
        Router::get('/models', [DelightfulFlowAIModelFlowAdminApi::class, 'getEnabled']);
        Router::get('/node-versions', [DelightfulFlowFlowAdminApi::class, 'nodeVersions']);
        Router::post('/node-template', [DelightfulFlowFlowAdminApi::class, 'nodeTemplate']);
        Router::post('/node-debug', [DelightfulFlowFlowAdminApi::class, 'singleDebugNode']);
        Router::post('/{flowId}/flow-debug', [DelightfulFlowFlowAdminApi::class, 'flowDebug']);
        Router::post('', [DelightfulFlowFlowAdminApi::class, 'saveFlow']);
        Router::post('/queries', [DelightfulFlowFlowAdminApi::class, 'queries']);
        Router::post('/queries/tools', [DelightfulFlowFlowAdminApi::class, 'queryTools']);
        Router::post('/queries/tool-sets', [DelightfulFlowFlowAdminApi::class, 'queryToolSets']);
        Router::post('/queries/mcp-list', [DelightfulFlowFlowAdminApi::class, 'queryMCPList']);
        Router::post('/queries/knowledge', [DelightfulFlowFlowAdminApi::class, 'queryKnowledge']);
        Router::get('/{flowId}', [DelightfulFlowFlowAdminApi::class, 'show']);
        Router::get('/{flowId}/params', [DelightfulFlowFlowAdminApi::class, 'showParams']);
        Router::delete('/{flowId}', [DelightfulFlowFlowAdminApi::class, 'remove']);
        Router::post('/{flowId}/change-enable', [DelightfulFlowFlowAdminApi::class, 'changeEnable']);
        Router::post('/expression-data-source', [DelightfulFlowFlowAdminApi::class, 'expressionDataSource']);

        // 草稿箱
        Router::post('/{flowId}/draft', [DelightfulFlowDraftFlowAdminApi::class, 'save']);
        Router::post('/{flowId}/draft/queries', [DelightfulFlowDraftFlowAdminApi::class, 'queries']);
        Router::get('/{flowId}/draft/{draftId}', [DelightfulFlowDraftFlowAdminApi::class, 'show']);
        Router::delete('/{flowId}/draft/{draftId}', [DelightfulFlowDraftFlowAdminApi::class, 'remove']);

        // 版本
        Router::post('/{flowId}/version/publish', [DelightfulFlowVersionFlowAdminApi::class, 'publish']);
        Router::post('/{flowId}/version/queries', [DelightfulFlowVersionFlowAdminApi::class, 'queries']);
        Router::get('/{flowId}/version/{versionId}', [DelightfulFlowVersionFlowAdminApi::class, 'show']);
        Router::post('/{flowId}/version/{versionId}/rollback', [DelightfulFlowVersionFlowAdminApi::class, 'rollback']);

        // 测试集
        Router::post('/{flowId}/testcase', [DelightfulFlowTriggerTestcaseFlowAdminApi::class, 'save']);
        Router::post('/{flowId}/testcase/queries', [DelightfulFlowTriggerTestcaseFlowAdminApi::class, 'queries']);
        Router::get('/{flowId}/testcase/{testcaseId}', [DelightfulFlowTriggerTestcaseFlowAdminApi::class, 'show']);
        Router::delete('/{flowId}/testcase/{testcaseId}', [DelightfulFlowTriggerTestcaseFlowAdminApi::class, 'remove']);

        // 工具集
        Router::post('/tool-set', [DelightfulFlowToolSetApiFlow::class, 'save']);
        Router::post('/tool-set/queries', [DelightfulFlowToolSetApiFlow::class, 'queries']);
        Router::get('/tool-set/{code}', [DelightfulFlowToolSetApiFlow::class, 'show']);
        Router::delete('/tool-set/{code}', [DelightfulFlowToolSetApiFlow::class, 'destroy']);

        // API_KEY 管理
        Router::post('/{flowId}/api-key', [DelightfulFlowApiKeyFlowAdminApi::class, 'save']);
        Router::post('/{flowId}/api-key/queries', [DelightfulFlowApiKeyFlowAdminApi::class, 'queries']);
        Router::get('/{flowId}/api-key/{code}', [DelightfulFlowApiKeyFlowAdminApi::class, 'show']);
        Router::delete('/{flowId}/api-key/{code}', [DelightfulFlowApiKeyFlowAdminApi::class, 'destroy']);
        Router::post('/{flowId}/api-key/{code}/rebuild', [DelightfulFlowApiKeyFlowAdminApi::class, 'changeSecretKey']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
