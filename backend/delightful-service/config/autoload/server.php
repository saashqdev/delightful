<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\HttpServer\Server;
use Hyperf\Server\Event;
use Hyperf\Server\ServerInterface;
use Hyperf\Server\SwowServer;

use function Hyperf\Support\env;

$httpPort = (int) env('HTTP_PORT', 9501);
! defined('LOCAL_HTTP_URL') && define('LOCAL_HTTP_URL', 'http://127.0.1:' . $httpPort);

$servers = [
    [
        'name' => 'http',
        'type' => ServerInterface::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => $httpPort,
        'callbacks' => [
            Event::ON_REQUEST => [Server::class, 'onRequest'],
        ],
    ],
    [
        'name' => 'socket-io',
        'type' => ServerInterface::SERVER_WEBSOCKET,
        'host' => '0.0.0.0',
        'port' => (int) env('WEBSOCKET_PORT', 9502),
        'sock_type' => 1,
        'callbacks' => [
            Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
            Event::ON_MESSAGE => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
            Event::ON_CLOSE => [Hyperf\WebSocketServer\Server::class, 'onClose'],
        ],
    ],
    // outatsingle测need,mock 所havethethird-party http call,enhancesingle测speeddegreeandstableproperty.
    [
        'name' => 'mock-http-service',
        'type' => ServerInterface::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => 9503,
        'callbacks' => [
            Event::ON_REQUEST => ['mock-http-service', 'onRequest'],
        ],
    ],
];
// !!!notice,openscheduletask pod thennotstart websocket service,onlystart http service
$enableCrontab = (bool) env('CRONTAB_ENABLE', true);
$enableCrontab && $servers = [$servers[0]];
return [
    'type' => SwowServer::class,
    'servers' => $servers,
    'settings' => [
        'pid_file' => BASE_PATH . '/runtime/hyperf.pid',
    ],
];
