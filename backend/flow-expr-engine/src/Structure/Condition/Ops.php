<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\FlowExprEngine\Structure\Condition;

enum Ops: string
{
    case And = 'AND';
    case Or = 'OR';

    public function getCondition(): string
    {
        return match ($this) {
            Ops::And => '&&',
            Ops::Or => '||',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
