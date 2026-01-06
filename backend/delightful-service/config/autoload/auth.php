<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Auth\Guard\WebsocketChatUserGuard;
use App\Infrastructure\Util\Auth\Guard\WebUserGuard;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Infrastructure\Utils\Auth\Guard\SandboxGuard;
use Delightful\BeDelightful\Interfaces\Authorization\Web\SandboxAuthorization;
use Qbhy\HyperfAuth\Provider\EloquentProvider;

return [
    'default' => [
        'guard' => 'web',
        'provider' => 'delightful-users',
    ],
    'guards' => [
        'web' => [
            'driver' => WebUserGuard::class,
            'provider' => 'delightful-users',
        ],
        // 需要解析 websocket 上下文中的 token 信息，因此跟 WebUserGuard 不同
        'websocket' => [
            'driver' => WebsocketChatUserGuard::class,
            'provider' => 'delightful-users',
        ],
        'sandbox' => [
            'driver' => SandboxGuard::class,
            'provider' => 'sandbox',
        ],
    ],
    'providers' => [
        // 麦吉自建用户体系
        'delightful-users' => [
            'driver' => EloquentProvider::class,
            'model' => DelightfulUserAuthorization::class,
        ],
        'sandbox' => [
            'driver' => EloquentProvider::class,
            'model' => SandboxAuthorization::class,
        ],
    ],
];
