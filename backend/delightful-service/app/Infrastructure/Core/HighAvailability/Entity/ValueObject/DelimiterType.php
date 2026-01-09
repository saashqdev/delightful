<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Entity\ValueObject;

use InvalidArgumentException;

/**
 * minuteseparatortypeenum.
 */
enum DelimiterType: string
{
    /**
     * highcanuseapplicationtype+modeltype+organizationencodingminuteseparator.
     */
    case HIGH_AVAILABILITY = '||';

    /**
     * get所haveminuteseparatortypevaluearray.
     */
    public static function values(): array
    {
        return [
            self::HIGH_AVAILABILITY->value,
        ];
    }

    /**
     * checkwhetherisvalidminuteseparatortype.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::values(), true);
    }

    /**
     * fromstringcreateenuminstance.
     */
    public static function fromString(string $type): self
    {
        return match ($type) {
            self::HIGH_AVAILABILITY->value => self::HIGH_AVAILABILITY,
            default => throw new InvalidArgumentException("invalidminuteseparatortype: {$type}"),
        };
    }
}
