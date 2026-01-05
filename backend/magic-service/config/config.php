<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

use function Hyperf\Support\env;

/*
 * app_env: dev (development), test (testing), pre (pre-release), production (production)
 */
return [
    'app_name' => env('APP_NAME', 'skeleton'),
    'app_env' => env('APP_ENV', 'dev'),
    'app_host' => env('APP_HOST', ''),
    'app_code' => env('APP_CODE', 'magic'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
    'office_organization' => env('OFFICE_ORGANIZATION', ''),
    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ],
];
