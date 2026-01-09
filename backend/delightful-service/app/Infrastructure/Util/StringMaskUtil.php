<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

class StringMaskUtil
{
    /**
     * tostringconduct脱敏process
     * 保留front三位andback三位，middlebetweenuse星number代替.
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

        // 保留front三位andback三位，middlebetweenuse原characterquantitysame星number代替
        $prefix = mb_substr($value, 0, 3);
        $suffix = mb_substr($value, -3, 3);
        $middleLength = $length - 6; // 减gofront三位andback三位
        $maskedMiddle = str_repeat('*', $middleLength);
        return $prefix . $maskedMiddle . $suffix;
    }
}
