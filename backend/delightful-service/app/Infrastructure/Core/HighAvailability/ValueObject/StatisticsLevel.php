<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * statistics级别枚举类.
 */
enum StatisticsLevel: int
{
    /**
     * statistics级别：秒级.
     */
    case LEVEL_SECOND = 0;

    /**
     * statistics级别：分钟级.
     */
    case LEVEL_MINUTE = 1;

    /**
     * statistics级别：小时级.
     */
    case LEVEL_HOUR = 2;

    /**
     * statistics级别：天级.
     */
    case LEVEL_DAY = 3;

    /**
     * getstatistics级别name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::LEVEL_SECOND => '秒级',
            self::LEVEL_MINUTE => '分钟级',
            self::LEVEL_HOUR => '小时级',
            self::LEVEL_DAY => '天级',
        };
    }

    /**
     * getstatistics级别name（静态method，useatcompatible旧code）.
     * @deprecated use枚举实例的 getName() method代替
     */
    public static function getLevelName(int|self $level): string
    {
        if (is_int($level)) {
            return match ($level) {
                self::LEVEL_SECOND->value => '秒级',
                self::LEVEL_MINUTE->value => '分钟级',
                self::LEVEL_HOUR->value => '小时级',
                self::LEVEL_DAY->value => '天级',
                default => '未知级别',
            };
        }

        return $level->getName();
    }
}
