<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\TaskScheduler\Entity\ValueObject;

enum TaskType: string
{
    /**
     * Do not repeat.
     */
    case NoRepeat = 'no_repeat';

    /**
     * Repeat daily.
     */
    case DailyRepeat = 'daily_repeat';

    /**
     * Repeat weekly.
     */
    case WeeklyRepeat = 'weekly_repeat';

    /**
     * Repeat monthly.
     */
    case MonthlyRepeat = 'monthly_repeat';

    /**
     * Repeat yearly.
     */
    case AnnuallyRepeat = 'annually_repeat';

    /**
     * Repeat on each weekday.
     */
    case WeekdayRepeat = 'weekday_repeat';

    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';

    /**
     * Custom repeat.
     */
    case CustomRepeat = 'custom_repeat';

    public function needDay(): bool
    {
        return in_array($this, [
            self::NoRepeat,
            self::MonthlyRepeat,
            self::AnnuallyRepeat,
            self::CustomRepeat,
        ]);
    }

    public function needTime(): bool
    {
        return in_array($this, [
            self::NoRepeat,
            self::DailyRepeat,
            self::WeeklyRepeat,
            self::MonthlyRepeat,
            self::AnnuallyRepeat,
            self::CustomRepeat,
        ]);
    }
}
