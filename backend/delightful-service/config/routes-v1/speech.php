<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Interfaces\Speech\Facade\Open\SpeechToTextStandardApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1', static function () {
    Router::addGroup('/speech', static function () {
        // Standard speech recognition
        Router::post('/submit', [SpeechToTextStandardApi::class, 'submit']);
        Router::post('/query/{taskId}', [SpeechToTextStandardApi::class, 'query']);

        // Large model speech recognition
        Router::post('/large-model/submit', [SpeechToTextStandardApi::class, 'submitLargeModel']);
        Router::post('/large-model/query/{requestId}', [SpeechToTextStandardApi::class, 'queryLargeModel']);

        // Flash speech recognition
        Router::post('/flash', [SpeechToTextStandardApi::class, 'flash']);
    });
});
