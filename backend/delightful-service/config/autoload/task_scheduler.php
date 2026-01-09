<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Periodically generate data for the next n days
    // If production environment, set to 3; if test environment, set to 365*2
    'crontab_days' => env('APP_ENV') == 'saas-test' ? 365 * 2 : 3,
    // Data older than n days will be cleaned up
    'clear_days' => 10,

    // Disable environment isolation
    'environment_enabled' => false,

    // Number of concurrent scheduled tasks, coroutine count control
    'concurrency' => 500,

    // Lock timeout
    'lock_timeout' => 600,

    // 是否privatedeploy
    'is_private_deploy' => (bool) env('IS_PRIVATE_DEPLOY', false),
];
