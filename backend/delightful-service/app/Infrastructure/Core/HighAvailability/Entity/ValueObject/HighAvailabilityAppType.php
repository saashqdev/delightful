<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * 高可用applicationtype枚举.
 */
enum HighAvailabilityAppType: string
{
    /**
     * model网关type.
     */
    case MODEL_GATEWAY = 'modelGateway';

    /**
     * get所有applicationtype值array.
     */
    public static function values(): array
    {
        return [
            self::MODEL_GATEWAY->value,
        ];
    }

    /**
     * check是否是valid的applicationtype.
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
            self::MODEL_GATEWAY->value => self::MODEL_GATEWAY,
            default => throw new InvalidArgumentException("invalid的高可用applicationtype: {$type}"),
        };
    }

    /**
     * getapplicationtype的description文本.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MODEL_GATEWAY => 'model网关',
        };
    }
}
