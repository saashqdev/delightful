<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * statisticslevel别枚举category.
 */
enum StatisticsLevel: int
{
    /**
     * statisticslevel别：secondlevel.
     */
    case LEVEL_SECOND = 0;

    /**
     * statisticslevel别：minute钟level.
     */
    case LEVEL_MINUTE = 1;

    /**
     * statisticslevel别：hourlevel.
     */
    case LEVEL_HOUR = 2;

    /**
     * statisticslevel别：daylevel.
     */
    case LEVEL_DAY = 3;

    /**
     * getstatisticslevel别name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::LEVEL_SECOND => 'secondlevel',
            self::LEVEL_MINUTE => 'minute钟level',
            self::LEVEL_HOUR => 'hourlevel',
            self::LEVEL_DAY => 'daylevel',
        };
    }

    /**
     * getstatisticslevel别name（静statemethod，useatcompatibleoldcode）.
     * @deprecated use枚举instance getName() method代替
     */
    public static function getLevelName(int|self $level): string
    {
        if (is_int($level)) {
            return match ($level) {
                self::LEVEL_SECOND->value => 'secondlevel',
                self::LEVEL_MINUTE->value => 'minute钟level',
                self::LEVEL_HOUR->value => 'hourlevel',
                self::LEVEL_DAY->value => 'daylevel',
                default => 'unknownlevel别',
            };
        }

        return $level->getName();
    }
}
