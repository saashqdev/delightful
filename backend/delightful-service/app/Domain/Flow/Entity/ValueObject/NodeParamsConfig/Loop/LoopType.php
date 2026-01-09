<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loop;

enum LoopType: string
{
    // count
    case Count = 'count';

    // traversearray
    case Array = 'array';

    // itemitemcompare
    case Condition = 'condition';
}
