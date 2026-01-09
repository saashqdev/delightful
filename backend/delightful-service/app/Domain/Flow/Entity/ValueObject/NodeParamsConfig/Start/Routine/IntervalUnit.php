<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum IntervalUnit: string
{
    /**
     * 间隔execute单位:天.
     */
    case Day = 'day';

    /**
     * 间隔execute单位:周.
     */
    case Week = 'week';

    /**
     * 间隔execute单位:月.
     */
    case Month = 'month';

    /**
     * 间隔execute单位:年.
     */
    case Year = 'year';
}
