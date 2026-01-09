<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

class StringMaskUtil
{
    /**
     * 对string进行脱敏process
     * 保留前三位和后三位，中间用星号代替.
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

        // 保留前三位和后三位，中间用原字符quantitysame的星号代替
        $prefix = mb_substr($value, 0, 3);
        $suffix = mb_substr($value, -3, 3);
        $middleLength = $length - 6; // 减去前三位和后三位
        $maskedMiddle = str_repeat('*', $middleLength);
        return $prefix . $maskedMiddle . $suffix;
    }
}
