<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Snowflake\MetaGenerator\RedisMilliSecondMetaGenerator;
use Hyperf\Snowflake\MetaGenerator\RedisSecondMetaGenerator;
use Hyperf\Snowflake\MetaGeneratorInterface;

// useatcalculate WorkerId  Key key,避免跟otherproject混use.
$snowflakeRedisKey = env('SNOWFLAKE_REDIS_KEY', 'delightful:snowflake:workerId');
# initDataCenterIdAndWorkerId methodmiddle,workerId and dataCenterId calculatemethodnot合理,导致meanwhilemost大只能have 31 pod.
# not如decrease \Hyperf\Snowflake\Configuration middle $dataCenterIdBits and $workerIdBits size,增大 $sequenceBits,by便single台机器each毫second能generatemore多雪flowerid,decrease特高andhairdownetc待time
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
