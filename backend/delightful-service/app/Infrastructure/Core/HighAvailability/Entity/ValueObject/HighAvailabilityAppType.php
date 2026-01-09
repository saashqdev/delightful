<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * highcanuseapplicationtype枚举.
 */
enum HighAvailabilityAppType: string
{
    /**
     * model网closetype.
     */
    case MODEL_GATEWAY = 'modelGateway';

    /**
     * get所haveapplicationtypevaluearray.
     */
    public static function values(): array
    {
        return [
            self::MODEL_GATEWAY->value,
        ];
    }

    /**
     * checkwhetherisvalidapplicationtype.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::values(), true);
    }

    /**
     * fromstringcreate枚举instance.
     */
    public static function fromString(string $type): self
    {
        return match ($type) {
            self::MODEL_GATEWAY->value => self::MODEL_GATEWAY,
            default => throw new InvalidArgumentException("invalidhighcanuseapplicationtype: {$type}"),
        };
    }

    /**
     * getapplicationtypedescriptiontext.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MODEL_GATEWAY => 'model网close',
        };
    }
}
