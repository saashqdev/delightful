<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Periodically generate data for the next n days
    'crontab_days' => 3,
    // Data older than n days will be cleared
    'clear_days' => 10,

    // Enable environment isolation
    'environment_enabled' => false,

    // Number of scheduled tasks executed concurrently; controls coroutine count
    'concurrency' => 500,

    // Lock timeout
    'lock_timeout' => 600,
];
