<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

enum IntervalUnit: string
{
    /**
     * 间隔executeunit:天.
     */
    case Day = 'day';

    /**
     * 间隔executeunit:周.
     */
    case Week = 'week';

    /**
     * 间隔executeunit:月.
     */
    case Month = 'month';

    /**
     * 间隔executeunit:年.
     */
    case Year = 'year';
}
