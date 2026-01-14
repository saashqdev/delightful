<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date;

use DateTime;
use DateTimeZone;
use BeDelightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class GetRFC1123DateTime extends AbstractMethod
{
    protected string $code = 'get_rfc1123_date_time';

    protected string $name = 'get_rfc1123_date_time';

    protected string $returnType = 'string';

    protected string $group = 'Date/Time';

    protected string $desc = 'Get date and time in RFC 1123 format; e.g.: Sat, 21 Oct 2021 07:28:00 GMT';

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

            // Create DateTime object from timestamp and set to UTC
            $datetime = new DateTime('@' . $time);
            $datetime->setTimezone(new DateTimeZone('UTC'));

            // Format as RFC 1123 with proper GMT suffix
            return $datetime->format('D, d M Y H:i:s') . ' GMT';
        };
    }
}
