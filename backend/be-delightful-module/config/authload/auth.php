<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use BeDelightful\BeDelightful\Infrastructure\Utils\Auth\Guard\SandboxGuard;
use BeDelightful\BeDelightful\Interfaces\Authorization\Web\SandboxAuthorization;
use Qbhy\HyperfAuth\Provider\EloquentProvider;

return [
    'guards' => [
        'sandbox' => [
            'driver' => SandboxGuard::class,
            'provider' => 'sandbox',
        ],
    ],
    'providers' => [
        'sandbox' => [
            'driver' => EloquentProvider::class,
            'model' => SandboxAuthorization::class,
        ],
    ],
];
