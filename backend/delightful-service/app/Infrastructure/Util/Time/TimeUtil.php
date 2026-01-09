<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Time;

class TimeUtil
{
    // according to传入的starttime，return与whenfronttime的毫secondleveltime差
    public static function getMillisecondDiffFromNow(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }
}
