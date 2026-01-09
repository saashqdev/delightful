<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Time;

class TimeUtil
{
    // according topass instarttime,returnandwhenfronttimemillisecondssecondleveltimedifference
    public static function getMillisecondDiffFromNow(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }
}
