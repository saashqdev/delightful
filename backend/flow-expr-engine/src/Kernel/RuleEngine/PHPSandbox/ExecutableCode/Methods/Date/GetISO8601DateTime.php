<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date;

use DateTime;
use DateTimeZone;
use BeDelightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class GetISO8601DateTime extends AbstractMethod
{
    protected string $code = 'get_iso8601_date_time';

    protected string $name = 'get_iso8601_date_time';

    protected string $returnType = 'string';

    protected string $group = 'Date/Time';

    protected string $desc = 'Get date and time in ISO 8601 format (UTC time); e.g.: 2021-01-01T00:00:00Z';

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

            // Create DateTime object from timestamp and convert to UTC
            $datetime = new DateTime('@' . $time);
            $datetime->setTimezone(new DateTimeZone('UTC'));

            // Format as ISO 8601 with UTC timezone (Z suffix)
            return $datetime->format('Y-m-d\TH:i:s\Z');
        };
    }
}
