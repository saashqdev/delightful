<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class Round extends AbstractMethod
{
    protected string $code = 'round';

    protected string $name = 'Round';

    protected string $returnType = 'float';

    protected string $group = 'Built-in';

    protected string $desc = 'Returns the rounded value of num to specified precision (decimal places).';

    protected array $args = [
        [
            'name' => 'num',
            'type' => 'int|float',
            'desc' => 'The value to round.',
        ],
        [
            'name' => 'precision',
            'type' => 'int',
            'desc' => "Optional number of decimal places to round to.\nIf positive, num is rounded to precision decimal places",
        ],
    ];
}
