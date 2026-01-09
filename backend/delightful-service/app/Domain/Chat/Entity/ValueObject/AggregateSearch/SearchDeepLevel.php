<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AggregateSearch;

enum SearchDeepLevel: int
{
    // 简单search
    case SIMPLE = 1;

    // 深degreesearch
    case DEEP = 2;
}
