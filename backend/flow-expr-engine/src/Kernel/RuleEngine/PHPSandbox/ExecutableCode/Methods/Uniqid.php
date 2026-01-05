<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class Uniqid extends AbstractMethod
{
    protected string $code = 'uniqid';

    protected string $name = 'Generate Unique ID';

    protected string $returnType = 'string';

    protected string $group = 'Built-in';

    protected string $desc = 'Generate a unique ID';

    protected array $args = [
        [
            'name' => 'prefix',
            'desc' => 'Useful parameter. For example: if generating unique IDs on multiple hosts at the same microsecond.\nIf prefix is empty, the returned string is 13 characters long. If more_entropy is true, the returned string is 23 characters long.',
            'type' => 'string',
        ],
        [
            'name' => 'more_entropy',
            'desc' => 'If set to true, uniqid() will add additional entropy (using combined linear congruential generator) at the end of the return value, making unique IDs more unique.',
            'type' => 'bool',
        ],
    ];
}
