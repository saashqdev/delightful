<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

use Overtrue\ChineseCalendar\Calendar;

class LunarDayUtil
{
    public static function convertToLunarDay(string $date): string
    {
        $dateParts = explode('-', $date);
        // Build calendar instance
        /* @phpstan-ignore-next-line */
        $lunarInfo = (new Calendar())->solar($dateParts[0], $dateParts[1], $dateParts[2]);
        return $lunarInfo['lunar_day_chinese']; // Return the lunar day
    }
}
