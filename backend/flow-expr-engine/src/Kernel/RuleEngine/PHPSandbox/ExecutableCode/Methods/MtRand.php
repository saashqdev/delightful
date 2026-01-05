<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class MtRand extends AbstractMethod
{
    protected string $code = 'mt_rand';

    protected string $name = 'Random Integer';

    protected string $returnType = 'int|false';

    protected string $group = 'Built-in';

    protected string $desc = 'Generates a random value using the Mersenne Twister random number generator';

    protected array $args = [
        [
            'name' => 'min',
            'desc' => 'Optional minimum value to return (default: 0)',
            'type' => 'int',
        ],
        [
            'name' => 'max',
            'desc' => 'Optional maximum value to return (default: mt_getrandmax())',
            'type' => 'int',
        ],
    ];
}
