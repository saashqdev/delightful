<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AggregateSearch;

enum SearchDeepLevel: int
{
    // simplesinglesearch
    case SIMPLE = 1;

    // deepdegreesearch
    case DEEP = 2;
}
