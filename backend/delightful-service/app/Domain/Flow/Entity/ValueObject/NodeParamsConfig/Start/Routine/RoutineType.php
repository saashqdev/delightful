<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum RoutineType: string
{
    /**
     * not重复.
     */
    case NoRepeat = 'no_repeat';

    /**
     * eachday重复.
     */
    case DailyRepeat = 'daily_repeat';

    /**
     * eachweek重复.
     */
    case WeeklyRepeat = 'weekly_repeat';

    /**
     * eachmonth重复.
     */
    case MonthlyRepeat = 'monthly_repeat';

    /**
     * eachyear重复.
     */
    case AnnuallyRepeat = 'annually_repeat';

    /**
     * eachworkday重复.
     */
    case WeekdayRepeat = 'weekday_repeat';

    /**
     * customize重复.
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
