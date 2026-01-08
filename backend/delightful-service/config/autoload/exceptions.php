<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\Exception\Handler\AppExceptionHandler;
use App\Infrastructure\Core\Exception\Handler\BusinessExceptionHandler;
use App\Infrastructure\Core\Exception\Handler\InvalidArgumentExceptionHandler;
use App\Infrastructure\Core\Exception\Handler\OpenAIProxyExceptionHandler;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;

return [
    'handler' => [
        'http' => [
            OpenAIProxyExceptionHandler::class,
            BusinessExceptionHandler::class,
            InvalidArgumentExceptionHandler::class,
            HttpExceptionHandler::class,
            AppExceptionHandler::class,
        ],
        // WebSocket exceptions are only effective for ON_HAND_SHAKE.
        // ON_MESSAGE will not trigger exception handler dispatch
        'socket-io' => [
            BusinessExceptionHandler::class,
            InvalidArgumentExceptionHandler::class,
            HttpExceptionHandler::class,
            AppExceptionHandler::class,
        ],
    ],
];
