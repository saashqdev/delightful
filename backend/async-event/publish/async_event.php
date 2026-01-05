<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Retry configuration
    'retry' => [
        // Retry interval in seconds
        'interval' => 600,
        // Maximum retry times
        'times' => 3,
    ],

    // Callback after maximum retries, e.g., send DingTalk message
    'max_retry_callback' => [null],

    // Coroutine context copy keys
    'context_copy_keys' => ['request-id'],

    // Whether to clear historical events
    'clear_history' => env('ASYNC_EVENT_CLEAR_HISTORY', true),
];
