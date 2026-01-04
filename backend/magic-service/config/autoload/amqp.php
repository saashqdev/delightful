<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use Hyperf\Amqp\IO\IOFactory;

use function Hyperf\Support\env;

// Important: if cron jobs are enabled on a pod, disable MQ there to avoid cron blocking MQ consumption.
$enableCrontab = (bool) env('CRONTAB_ENABLE', true);
return [
    // Architecture layering may publish/consume message and seq separately, so switches are separate
    'enable_chat_message' => ! $enableCrontab,
    'enable_chat_seq' => ! $enableCrontab,
    // Global MQ toggle
    'enable' => ! $enableCrontab,
    'default' => [
        'host' => env('AMQP_HOST', 'localhost'),
        'port' => (int) env('AMQP_PORT', 5672),
        'user' => env('AMQP_USER', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest'),
        'vhost' => env('AMQP_VHOST', '/'),
        'open_ssl' => false,
        'concurrent' => [
            'limit' => 6,
        ],
        'pool' => [
            'connections' => 4,
        ],
        'io' => IOFactory::class,
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            'connection_timeout' => 3,
            // Try to maintain twice value heartbeat as much as possible
            'read_write_timeout' => 6,
            'context' => null,
            'keepalive' => true,
            // Try to ensure that the consumption time of each message is less than the heartbeat time as much as possible
            'heartbeat' => 3,
            'channel_rpc_timeout' => 0.0,
            'close_on_destruct' => false,
            'max_idle_channels' => 10,
        ],
    ],
];
