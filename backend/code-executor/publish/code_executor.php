<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */
use BeDelightful\CodeExecutor\Executor\Aliyun\AliyunExecutor;

use function Hyperf\Support\env;

return [
    'executor' => env('CODE_EXECUTOR', 'aliyun'),
    'executors' => [
        'aliyun' => [
            'executor' => AliyunExecutor::class,
            'access_key' => env('CODE_EXECUTOR_ALIYUN_ACCESS_KEY'),
            'secret_key' => env('CODE_EXECUTOR_ALIYUN_SECRET_KEY'),
            'region' => env('CODE_EXECUTOR_ALIYUN_REGION'),
            'endpoint' => env('CODE_EXECUTOR_ALIYUN_ENDPOINT'),
            'function' => [
                'name' => env('CODE_EXECUTOR_ALIYUN_FUNCTION_NAME'),
                // You can override default configuration here
            ],
        ],
    ],
];
