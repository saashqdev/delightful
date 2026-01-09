<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use function Hyperf\Support\env;

return [
    // onedayat mostsendcount
    'day_max_count' => 30,
    // eachtimesendbetween隔60s
    'time_interval' => 60,
    'volcengine' => [
        'accessKey' => env('VOLCENGINE_SMS_ACCESS_KEY', ''),
        'secretKey' => env('VOLCENGINE_SMS_SECRET_KEY', ''),
    ],
];
