<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Condition;

enum ConditionItemType: string
{
    case Compare = 'compare';
    case Operation = 'operation';
}
