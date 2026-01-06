<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'open_api_host' => env('DELIGHTFUL_OPEN_API_HOST', ''),
    'auth' => [
        'open_platform_host' => env('DELIGHTFUL_OPEN_PLATFORM_HOST', ''),
        'accounts' => [
            'app' => [
                'app_id' => '',
                'app_secret' => '',
            ],
        ],
    ],
];
