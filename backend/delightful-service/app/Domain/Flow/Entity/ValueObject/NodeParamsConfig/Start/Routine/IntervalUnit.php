<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum IntervalUnit: string
{
    /**
     * betweenseparatorexecuteunit:day.
     */
    case Day = 'day';

    /**
     * betweenseparatorexecuteunit:week.
     */
    case Week = 'week';

    /**
     * betweenseparatorexecuteunit:month.
     */
    case Month = 'month';

    /**
     * betweenseparatorexecuteunit:year.
     */
    case Year = 'year';
}
