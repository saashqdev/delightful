<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum IntervalUnit: string
{
    /**
     * between隔executeunit:day.
     */
    case Day = 'day';

    /**
     * between隔executeunit:week.
     */
    case Week = 'week';

    /**
     * between隔executeunit:month.
     */
    case Month = 'month';

    /**
     * between隔executeunit:year.
     */
    case Year = 'year';
}
