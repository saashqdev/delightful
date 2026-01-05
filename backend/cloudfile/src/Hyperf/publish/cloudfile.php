<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'storages' => [
        'file_service' => [
            'adapter' => 'file_service',
            'config' => [
                'host' => '',
                'platform' => '',
                'key' => '',
            ],
        ],
        'aliyun' => [
            'adapter' => 'aliyun',
            'config' => [
                'accessId' => '',
                'accessSecret' => '',
                'bucket' => '',
                'endpoint' => '',
                'role_arn' => '',
            ],
        ],
        'tos' => [
            'adapter' => 'tos',
            'config' => [
                'region' => '',
                'endpoint' => '',
                'ak' => '',
                'sk' => '',
                'bucket' => '',
                'trn' => '',
            ],
        ],
        'minio' => [
            'adapter' => 'minio',
            'config' => [
                // MinIO service address, e.g.: http://localhost:9000
                'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
                // Region, default is us-east-1
                'region' => env('MINIO_REGION', 'us-east-1'),
                // Access Key
                'accessKey' => env('MINIO_ACCESS_KEY', ''),
                // Secret Key
                'secretKey' => env('MINIO_SECRET_KEY', ''),
                // Bucket name
                'bucket' => env('MINIO_BUCKET', ''),
                // MinIO must use path-style access
                'use_path_style_endpoint' => true,
                // SDK version
                'version' => 'latest',
                // Optional: Role ARN for STS temporary credentials
                'role_arn' => env('MINIO_ROLE_ARN', ''),
                // Optional: STS service endpoint (if different from main service)
                'sts_endpoint' => env('MINIO_STS_ENDPOINT', ''),
            ],
            // Optional: Whether to enable public read
            'public_read' => false,
            // Optional: Default options
            'options' => [],
        ],
    ],
];
