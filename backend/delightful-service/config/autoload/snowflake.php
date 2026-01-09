<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Snowflake\MetaGenerator\RedisMilliSecondMetaGenerator;
use Hyperf\Snowflake\MetaGenerator\RedisSecondMetaGenerator;
use Hyperf\Snowflake\MetaGeneratorInterface;

// 用于计算 WorkerId 的 Key 键,避免跟其他项目混用.
$snowflakeRedisKey = env('SNOWFLAKE_REDIS_KEY', 'delightful:snowflake:workerId');
# initDataCenterIdAndWorkerId method中,workerId 和 dataCenterId 的计算method不合理,导致同时最大只能有 31 个pod.
# 不如减少 \Hyperf\Snowflake\Configuration 中 $dataCenterIdBits 和 $workerIdBits 的size,增大 $sequenceBits,以便单台机器每毫秒能generate更多的雪花id,减少特高并发下的等待时间
return [
    'begin_second' => MetaGeneratorInterface::DEFAULT_BEGIN_SECOND,
    RedisMilliSecondMetaGenerator::class => [
        'pool' => 'default',
        'key' => $snowflakeRedisKey,
    ],
    RedisSecondMetaGenerator::class => [
        'pool' => 'default',
        'key' => $snowflakeRedisKey,
    ],
];
