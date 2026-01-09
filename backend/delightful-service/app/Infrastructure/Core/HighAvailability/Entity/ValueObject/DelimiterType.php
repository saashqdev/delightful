<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * 分隔符type枚举.
 */
enum DelimiterType: string
{
    /**
     * 高可用应用type+模型type+organization编码的分隔符.
     */
    case HIGH_AVAILABILITY = '||';

    /**
     * get所有分隔符type值array.
     */
    public static function values(): array
    {
        return [
            self::HIGH_AVAILABILITY->value,
        ];
    }

    /**
     * check是否是valid的分隔符type.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::values(), true);
    }

    /**
     * 从stringcreate枚举实例.
     */
    public static function fromString(string $type): self
    {
        return match ($type) {
            self::HIGH_AVAILABILITY->value => self::HIGH_AVAILABILITY,
            default => throw new InvalidArgumentException("invalid的分隔符type: {$type}"),
        };
    }
}
