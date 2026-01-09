<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loop;

enum LoopType: string
{
    // 计数
    case Count = 'count';

    // 遍历array
    case Array = 'array';

    // itemitemcompare
    case Condition = 'condition';
}
