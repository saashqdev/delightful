<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

class Time extends AbstractMethod
{
    protected string $code = 'time';

    protected string $name = 'Current Unix Timestamp';

    protected string $returnType = 'int';

    protected string $group = 'Built-in';

    protected string $desc = 'Return the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) to the current time.';

    protected array $args = [];
}
