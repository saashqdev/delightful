<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\ValueObject\StorageBucketType;

$fileDriver = env('FILE_DRIVER');

$storages = [];
switch ($fileDriver) {
    case 'local':
        $root = env('FILE_LOCAL_ROOT');
        if (empty($root)) {
            $root = BASE_PATH . '/storage/files';
        }
        if (empty(env('FILE_LOCAL_READ_HOST', ''))) {
            throw new InvalidArgumentException('FILE_LOCAL_READ_HOST is required');
        }
        if (empty(env('FILE_LOCAL_WRITE_HOST', ''))) {
            throw new InvalidArgumentException('FILE_LOCAL_WRITE_HOST is required');
        }
        $storages[StorageBucketType::Private->value] = [
            'adapter' => 'local',
            'config' => [
                'root' => $root,
                'read_host' => env('FILE_LOCAL_READ_HOST', ''),
                'write_host' => env('FILE_LOCAL_WRITE_HOST', '') . '/api/v1/file/upload',
            ],
        ];
        $storages[StorageBucketType::Public->value] = [
            'adapter' => 'local',
            'config' => [
                'root' => $root,
                'read_host' => env('FILE_LOCAL_READ_HOST', ''),
                'write_host' => env('FILE_LOCAL_WRITE_HOST', '') . '/api/v1/file/upload',
            ],
            'public_read' => true,
        ];
        break;
    case 'tos':
        if (empty(env('FILE_PRIVATE_TOS_REGION', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_TOS_REGION is required');
        }
        if (empty(env('FILE_PRIVATE_TOS_ENDPOINT', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_TOS_ENDPOINT is required');
        }
        if (empty(env('FILE_PRIVATE_TOS_AK', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_TOS_AK is required');
        }
        if (empty(env('FILE_PRIVATE_TOS_SK', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_TOS_SK is required');
        }
        if (empty(env('FILE_PRIVATE_TOS_BUCKET', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_TOS_BUCKET is required');
        }

        if (empty(env('FILE_PUBLIC_TOS_REGION', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_TOS_REGION is required');
        }
        if (empty(env('FILE_PUBLIC_TOS_ENDPOINT', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_TOS_ENDPOINT is required');
        }
        if (empty(env('FILE_PUBLIC_TOS_AK', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_TOS_AK is required');
        }
        if (empty(env('FILE_PUBLIC_TOS_SK', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_TOS_SK is required');
        }
        if (empty(env('FILE_PUBLIC_TOS_BUCKET', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_TOS_BUCKET is required');
        }
        $storages[StorageBucketType::Private->value] = [
            'adapter' => 'tos',
            'config' => [
                'region' => env('FILE_PRIVATE_TOS_REGION', ''),
                'endpoint' => env('FILE_PRIVATE_TOS_ENDPOINT', ''),
                'ak' => env('FILE_PRIVATE_TOS_AK', ''),
                'sk' => env('FILE_PRIVATE_TOS_SK', ''),
                'bucket' => env('FILE_PRIVATE_TOS_BUCKET', ''),
                'trn' => env('FILE_PRIVATE_TOS_TRN', ''),
            ],
        ];
        $storages[StorageBucketType::Public->value] = [
            'adapter' => 'tos',
            'config' => [
                'region' => env('FILE_PUBLIC_TOS_REGION', ''),
                'endpoint' => env('FILE_PUBLIC_TOS_ENDPOINT', ''),
                'ak' => env('FILE_PUBLIC_TOS_AK', ''),
                'sk' => env('FILE_PUBLIC_TOS_SK', ''),
                'bucket' => env('FILE_PUBLIC_TOS_BUCKET', ''),
                'trn' => env('FILE_PUBLIC_TOS_TRN', ''),
            ],
            'public_read' => env('FILE_PUBLIC_TOS_PUBLIC_READ', true),
        ];
        break;
    case 'oss':
        if (empty(env('FILE_PRIVATE_ALIYUN_ACCESS_ID', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_ALIYUN_ACCESS_ID is required');
        }
        if (empty(env('FILE_PRIVATE_ALIYUN_ACCESS_SECRET', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_ALIYUN_ACCESS_SECRET is required');
        }
        if (empty(env('FILE_PRIVATE_ALIYUN_BUCKET', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_ALIYUN_BUCKET is required');
        }
        if (empty(env('FILE_PRIVATE_ALIYUN_ENDPOINT', ''))) {
            throw new InvalidArgumentException('FILE_PRIVATE_ALIYUN_ENDPOINT is required');
        }

        if (empty(env('FILE_PUBLIC_ALIYUN_ACCESS_ID', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_ALIYUN_ACCESS_ID is required');
        }
        if (empty(env('FILE_PUBLIC_ALIYUN_ACCESS_SECRET', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_ALIYUN_ACCESS_SECRET is required');
        }
        if (empty(env('FILE_PUBLIC_ALIYUN_BUCKET', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_ALIYUN_BUCKET is required');
        }
        if (empty(env('FILE_PUBLIC_ALIYUN_ENDPOINT', ''))) {
            throw new InvalidArgumentException('FILE_PUBLIC_ALIYUN_ENDPOINT is required');
        }

        $storages[StorageBucketType::Private->value] = [
            'adapter' => 'aliyun',
            'config' => [
                'accessId' => env('FILE_PRIVATE_ALIYUN_ACCESS_ID', ''),
                'accessSecret' => env('FILE_PRIVATE_ALIYUN_ACCESS_SECRET', ''),
                'bucket' => env('FILE_PRIVATE_ALIYUN_BUCKET', ''),
                'endpoint' => env('FILE_PRIVATE_ALIYUN_ENDPOINT', ''),
                'role_arn' => env('FILE_PRIVATE_ALIYUN_ROLE_ARN', ''),
            ],
        ];
        $storages[StorageBucketType::Public->value] = [
            'adapter' => 'aliyun',
            'config' => [
                'accessId' => env('FILE_PUBLIC_ALIYUN_ACCESS_ID', ''),
                'accessSecret' => env('FILE_PUBLIC_ALIYUN_ACCESS_SECRET', ''),
                'bucket' => env('FILE_PUBLIC_ALIYUN_BUCKET', ''),
                'endpoint' => env('FILE_PUBLIC_ALIYUN_ENDPOINT', ''),
                'role_arn' => env('FILE_PUBLIC_ALIYUN_ROLE_ARN', ''),
            ],
            'public_read' => env('FILE_PUBLIC_ALIYUN_PUBLIC_READ', true),
        ];
        break;
    default:
        // Warning: no interruption needed, just output warning
        echo "\033[33mWarning: File driver not configured. File-related functions will not be available. Please check FILE_DRIVER configuration in .env file\033[0m" . PHP_EOL;
        break;
}

return [
    'storages' => $storages,
    'driver' => $fileDriver,
];
