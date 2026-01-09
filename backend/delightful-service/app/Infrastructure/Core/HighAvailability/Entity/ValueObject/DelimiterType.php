<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * minute隔符type枚举.
 */
enum DelimiterType: string
{
    /**
     * 高可useapplicationtype+modeltype+organizationencoding的minute隔符.
     */
    case HIGH_AVAILABILITY = '||';

    /**
     * get所haveminute隔符typevaluearray.
     */
    public static function values(): array
    {
        return [
            self::HIGH_AVAILABILITY->value,
        ];
    }

    /**
     * checkwhether是valid的minute隔符type.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::values(), true);
    }

    /**
     * fromstringcreate枚举实例.
     */
    public static function fromString(string $type): self
    {
        return match ($type) {
            self::HIGH_AVAILABILITY->value => self::HIGH_AVAILABILITY,
            default => throw new InvalidArgumentException("invalid的minute隔符type: {$type}"),
        };
    }
}
