<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Time;

class TimeUtil
{
    // according to传入的开始时间，return与when前时间的毫秒级时间差
    public static function getMillisecondDiffFromNow(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }
}
