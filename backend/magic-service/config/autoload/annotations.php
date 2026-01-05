<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Aws\WrappedHttpHandler;
use GuzzleHttp\BodySummarizer;
use Hyperf\SocketIOServer\Room\RedisAdapter;
use Hyperf\SocketIOServer\SocketIO;

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
        'class_map' => [
            // Class names to map => file path
            // Override three classes via class_map to customize chunk output under hyperf/swow
            // Response::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/Response.php',
            // ResponseEmitter::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/ResponseEmitter.php',
            // ServerConnection::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/ServerConnection.php',
            // socket-io server with swow driver
            SocketIO::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/SocketIoServer/SocketIO.php',
            RedisAdapter::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/SocketIoServer/RedisAdapter.php',
            // websocket server with swow driver
            //            Sender::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/WebSocketServer/Sender.php',
            BodySummarizer::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/GuzzleHttp/BodySummarizer.php',
            // AWS SDK error handling enhancement
            WrappedHttpHandler::class => BASE_PATH . '/app/Infrastructure/Core/ClassMap/Aws/WrappedHttpHandler.php',
        ],
    ],
];
