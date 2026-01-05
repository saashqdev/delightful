<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Cache\Driver\FileSystemDriver;
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Codec\Packer\PhpSerializerPacker;

/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'default' => [
        'driver' => RedisDriver::class,
        'packer' => PhpSerializerPacker::class,
        'prefix' => '',
    ],
    'file' => [
        'driver' => FileSystemDriver::class,
        'packer' => PhpSerializerPacker::class,
        'prefix' => '',
    ],
];
