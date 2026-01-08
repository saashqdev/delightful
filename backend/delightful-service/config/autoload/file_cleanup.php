<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Default expiration time (seconds) - 2 hours
    'default_expire_seconds' => 7200,

    // Crontab configuration
    'crontab' => [
        'batch_size' => 50,           // Files per batch
        'retry_batch_size' => 30,     // Retry records per batch
        'max_batches' => 20,          // Maximum batches to process
        'max_retry_batches' => 10,    // Maximum retry batches
    ],

    // Retry configuration
    'retry' => [
        'max_retries' => 3,           // Maximum retry attempts
        'retry_delay' => 300,         // Retry interval (seconds) - 5 minutes
    ],

    // Maintenance configuration
    'maintenance' => [
        'success_days_to_keep' => 7,  // Days to keep successful records
        'failed_days_to_keep' => 7,   // Days to keep failed records
        'enable_auto_maintenance' => true, // Whether to enable auto maintenance
    ],

    // Monitoring configuration
    'monitoring' => [
        'enable_detailed_logs' => true,     // Whether to enable detailed logs
        'warn_failed_threshold' => 100,     // Failed records warning threshold
        'warn_pending_threshold' => 500,    // Pending records warning threshold
    ],

    // Default configuration for different source types
    'source_types' => [
        'batch_compress' => [
            'expire_seconds' => 7200,        // 2小时
            'description' => '批量压缩文件',
        ],
        'temp_upload' => [
            'expire_seconds' => 3600,        // 1小时
            'description' => '临时上传文件',
        ],
        'ai_generated' => [
            'expire_seconds' => 86400,       // 24小时
            'description' => 'AI生成文件',
        ],
        'preview_cache' => [
            'expire_seconds' => 1800,        // 30分钟
            'description' => '预览缓存文件',
        ],
    ],
];
