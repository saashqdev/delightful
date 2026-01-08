<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\File\Facade\Admin\FileApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    Router::addGroup('/file', static function () {
        Router::post('/temporary-credential', [FileApi::class, 'getUploadTemporaryCredential']);
        // Public bucket download
        Router::post('/publicFileDownload', [FileApi::class, 'publicFileDownload']);
        // Different businesses may have default files and organizations can also upload for their users, so separate route
        // Get files for specified business according to different business types
        Router::get('/business-file', [FileApi::class, 'getFileByBusinessType']);
        // Upload to business
        Router::post('/upload-business-file', [FileApi::class, 'uploadBusinessType']);
        // Delete
        Router::post('/delete-business-file', [FileApi::class, 'deleteBusinessFile']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);

Router::addGroup('/api/v1', static function () {
    Router::addGroup('/file', static function () {
        Router::get('/default-icons', [FileApi::class, 'getDefaultIcons']);

        // Local file upload
        Router::post('/upload', [FileApi::class, 'fileUpload']);
    });
});
