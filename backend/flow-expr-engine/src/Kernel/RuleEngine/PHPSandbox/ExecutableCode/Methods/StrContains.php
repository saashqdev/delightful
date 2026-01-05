<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class StrContains extends AbstractMethod
{
    protected string $code = 'str_contains';

    protected string $name = 'String Contains';

    protected string $returnType = 'boolean';

    protected string $group = 'Built-in';

    protected string $desc = 'Performs a case-sensitive check to determine if needle is contained in haystack.';

    protected array $args = [
        [
            'name' => 'haystack',
            'type' => 'string',
            'desc' => 'The string to search in.',
        ],
        [
            'name' => 'needle',
            'type' => 'string',
            'desc' => 'The substring to search for in haystack.',
        ],
    ];
}
