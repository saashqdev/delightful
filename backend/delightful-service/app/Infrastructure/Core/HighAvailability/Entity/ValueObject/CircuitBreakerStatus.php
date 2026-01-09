<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * circuit break器status枚举.
 */
enum CircuitBreakerStatus: string
{
    /**
     * closestatus - normalservicemiddle.
     */
    case CLOSED = 'closed';

    /**
     * startstatus - circuit breakmiddle.
     */
    case OPEN = 'open';

    /**
     * 半openstatus - 尝试restoremiddle.
     */
    case HALF_OPEN = 'half_open';

    /**
     * get所havestatusvaluearray.
     */
    public static function values(): array
    {
        return [
            self::CLOSED->value,
            self::OPEN->value,
            self::HALF_OPEN->value,
        ];
    }

    /**
     * checkwhetherisvalidstatusvalue
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }

    /**
     * fromstringcreate枚举实例.
     */
    public static function fromString(string $status): self
    {
        return match ($status) {
            self::CLOSED->value => self::CLOSED,
            self::OPEN->value => self::OPEN,
            self::HALF_OPEN->value => self::HALF_OPEN,
            default => throw new InvalidArgumentException("Invalid circuit breaker status: {$status}"),
        };
    }
}
