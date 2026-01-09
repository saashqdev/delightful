<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Snowflake\MetaGenerator\RedisMilliSecondMetaGenerator;
use Hyperf\Snowflake\MetaGenerator\RedisSecondMetaGenerator;
use Hyperf\Snowflake\MetaGeneratorInterface;

// useatcalculate WorkerId  Key key,avoid跟otherproject混use.
$snowflakeRedisKey = env('SNOWFLAKE_REDIS_KEY', 'delightful:snowflake:workerId');
# initDataCenterIdAndWorkerId methodmiddle,workerId and dataCenterId calculatemethodnot合理,导致meanwhilemostbigonlycanhave 31 pod.
# not如decrease \Hyperf\Snowflake\Configuration middle $dataCenterIdBits and $workerIdBits size,增big $sequenceBits,by便single台机器each毫secondcangeneratemore多雪flowerid,decrease特highandhairdownetc待time
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
