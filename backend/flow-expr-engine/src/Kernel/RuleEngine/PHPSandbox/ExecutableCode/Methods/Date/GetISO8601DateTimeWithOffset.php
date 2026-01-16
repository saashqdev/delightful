<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date;

use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class GetISO8601DateTimeWithOffset extends AbstractMethod
{
    protected string $code = 'get_iso8601_date_time_with_offset';

    protected string $name = 'get_iso8601_date_time_with_offset';

    protected string $returnType = 'string';

    protected string $group = 'Date/Time';

    protected string $desc = 'Get date and time in ISO 8601 format with timezone offset; e.g.: 2021-01-01T00:00:00+08:00';

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
            $timezoneOffset = date('P', $time);
            return date('Y-m-d\TH:i:s', $time) . $timezoneOffset;
        };
    }
}
