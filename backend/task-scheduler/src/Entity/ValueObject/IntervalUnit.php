<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\TaskScheduler\Entity\ValueObject;

enum IntervalUnit: string
{
    /**
     * Interval unit: day.
     */
    case Day = 'day';

    /**
     * Interval unit: week.
     */
    case Week = 'week';

    /**
     * Interval unit: month.
     */
    case Month = 'month';

    /**
     * Interval unit: year.
     */
    case Year = 'year';
}
