<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

class StringMaskUtil
{
    /**
     * tostringconductdesensitizeprocess
     * retainfrontthreepositionandbackthreeposition,middlebetweenusestarnumberreplace.
     */
    public static function mask(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $length = mb_strlen($value);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        // retainfrontthreepositionandbackthreeposition,middlebetweenuseoriginalcharacterquantitysamestarnumberreplace
        $prefix = mb_substr($value, 0, 3);
        $suffix = mb_substr($value, -3, 3);
        $middleLength = $length - 6; // subtractgofrontthreepositionandbackthreeposition
        $maskedMiddle = str_repeat('*', $middleLength);
        return $prefix . $maskedMiddle . $suffix;
    }
}
