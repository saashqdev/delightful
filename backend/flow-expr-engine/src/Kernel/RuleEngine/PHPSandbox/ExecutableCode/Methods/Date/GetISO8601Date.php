<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date;

use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class GetISO8601Date extends AbstractMethod
{
    protected string $code = 'get_iso8601_date';

    protected string $name = 'get_iso8601_date';

    protected string $returnType = 'string';

    protected string $group = 'Date/Time';

    protected string $desc = 'Get date in ISO 8601 format (date part only); e.g.: 2021-01-01';

    protected array $args = [
        [
            'name' => 'time',
            'type' => 'int',
            'desc' => 'The timestamp to calculate. Defaults to current time',
        ],
    ];

    public function getFunction(): ?callable
    {
        return function (null|int|string $time = null): string {
            $time = $time ?? time();
            if (is_string($time)) {
                $time = strtotime($time) ?: time();
            }
            return date('Y-m-d', $time);
        };
    }
}
