<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * 熔断器status枚举.
 */
enum CircuitBreakerStatus: string
{
    /**
     * closestatus - 正常服务中.
     */
    case CLOSED = 'closed';

    /**
     * 开启status - 熔断中.
     */
    case OPEN = 'open';

    /**
     * 半开status - 尝试restore中.
     */
    case HALF_OPEN = 'half_open';

    /**
     * get所有status值array.
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
     * 检查是否是有效的status值
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }

    /**
     * 从stringcreate枚举实例.
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
