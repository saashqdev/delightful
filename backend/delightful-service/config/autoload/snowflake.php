<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Snowflake\MetaGenerator\RedisMilliSecondMetaGenerator;
use Hyperf\Snowflake\MetaGenerator\RedisSecondMetaGenerator;
use Hyperf\Snowflake\MetaGeneratorInterface;

// useat计算 WorkerId 的 Key 键,避免跟其他project混use.
$snowflakeRedisKey = env('SNOWFLAKE_REDIS_KEY', 'delightful:snowflake:workerId');
# initDataCenterIdAndWorkerId methodmiddle,workerId 和 dataCenterId 的计算methodnot合理,导致meanwhilemost大只能have 31 pod.
# not如减少 \Hyperf\Snowflake\Configuration middle $dataCenterIdBits 和 $workerIdBits 的size,增大 $sequenceBits,by便单台机器each毫second能generatemore多的雪flowerid,减少特高并hairdown的etc待time
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
