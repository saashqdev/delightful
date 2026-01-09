<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum RoutineType: string
{
    /**
     * notduplicate.
     */
    case NoRepeat = 'no_repeat';

    /**
     * eachdayduplicate.
     */
    case DailyRepeat = 'daily_repeat';

    /**
     * eachweekduplicate.
     */
    case WeeklyRepeat = 'weekly_repeat';

    /**
     * eachmonthduplicate.
     */
    case MonthlyRepeat = 'monthly_repeat';

    /**
     * eachyearduplicate.
     */
    case AnnuallyRepeat = 'annually_repeat';

    /**
     * eachworkdayduplicate.
     */
    case WeekdayRepeat = 'weekday_repeat';

    /**
     * customizeduplicate.
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
