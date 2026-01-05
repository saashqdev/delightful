<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class Md5 extends AbstractMethod
{
    protected string $code = 'md5';

    protected string $name = 'MD5 Hash';

    protected string $returnType = 'string';

    protected string $group = 'Built-in';

    protected string $desc = 'Calculate the MD5 hash of a string';

    protected array $args = [
        [
            'name' => 'string',
            'type' => 'string',
            'desc' => 'The string to calculate.',
        ],
        [
            'name' => 'binary',
            'type' => 'boolean',
            'desc' => 'If the optional binary is set to true, then the md5 digest is returned in raw binary format with a length of 16.',
        ],
    ];
}
