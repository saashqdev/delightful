<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Asr\Facade\AsrApi;
use Hyperf\HttpServer\Router\Router;

// ASR speech-recognition routes (RESTful style)
Router::addGroup('/api/v1/asr', static function () {
    // JWT token resource management
    Router::get('/tokens', [AsrApi::class, 'show']);        // Get the current user's JWT token
    Router::delete('/tokens', [AsrApi::class, 'destroy']);  // Clear the current user's cached JWT token
    // Upload token management for recordings
    Router::get('/upload-tokens', [AsrApi::class, 'getUploadToken']);  // Get the STS token for uploading recordings
    // Recording status reporting
    Router::post('/status', [AsrApi::class, 'reportStatus']); // Report recording status (start|recording|paused|stopped)
    // Recording summary service
    Router::post('/summary', [AsrApi::class, 'summary']); // Query recording summary status (includes processing logic)
}, ['middleware' => [RequestContextMiddleware::class]]);
