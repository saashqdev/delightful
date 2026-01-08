<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Mode\Facade\AdminModeApi;
use App\Interfaces\Mode\Facade\AdminModeGroupApi;
use App\Interfaces\Mode\Facade\ModeApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    Router::addGroup('/official/admin', static function () {
        // Mode management
        Router::addGroup('/modes', static function () {
            // Get mode list
            Router::get('', [AdminModeApi::class, 'getModes']);
            // Get default mode
            Router::get('/default', [AdminModeApi::class, 'getDefaultMode']);
            // Create mode
            Router::post('', [AdminModeApi::class, 'createMode']);
            // Get mode detail
            Router::get('/{id}', [AdminModeApi::class, 'getMode']);
            // Get mode detail (unlinked)
            Router::get('/origin/{id}', [AdminModeApi::class, 'getOriginMode']);
            // Update mode
            Router::put('/{id}', [AdminModeApi::class, 'updateMode']);
            // Update mode status
            Router::put('/{id}/status', [AdminModeApi::class, 'updateModeStatus']);
            // Save mode configuration
            Router::put('/{id}/config', [AdminModeApi::class, 'saveModeConfig']);
        });

        // Mode group management
        Router::addGroup('/mode-groups', static function () {
            // Get group list by mode ID
            Router::get('/mode/{modeId}', [AdminModeGroupApi::class, 'getGroupsByModeId']);
            // Get group detail
            Router::get('/{groupId}', [AdminModeGroupApi::class, 'getGroupDetail']);
            // Create group
            Router::post('', [AdminModeGroupApi::class, 'createGroup']);
            // Update group
            Router::put('/{groupId}', [AdminModeGroupApi::class, 'updateGroup']);
            // Delete group
            Router::delete('/{groupId}', [AdminModeGroupApi::class, 'deleteGroup']);
        });
    });
    Router::addGroup('/modes', static function () {
        Router::get('', [ModeApi::class, 'getModes']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
