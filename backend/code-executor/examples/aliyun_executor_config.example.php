<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */
return [
    'access_key' => 'Your Alibaba Cloud AccessKey',
    'secret_key' => 'Your Alibaba Cloud SecretKey',
    'region' => 'cn-shenzhen', // Your region
    'endpoint' => 'fc.cn-shenzhen.aliyuncs.com', // Function Compute endpoint
    'function' => [
        'name' => 'test-code-runner',
        // You can override default configuration here
        'code_package_path' => __DIR__ . '/../runner',
    ],
];
