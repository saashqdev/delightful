<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Codec\Packer\PhpSerializerPacker;

return [
    'default' => [
        'driver' => RedisDriver::class,
        'packer' => PhpSerializerPacker::class,
        'prefix' => 'delightful:',
        // 一些经典为空的数据不缓存
        'skip_cache_results' => [
            null, '', [],
        ],
    ],
];
