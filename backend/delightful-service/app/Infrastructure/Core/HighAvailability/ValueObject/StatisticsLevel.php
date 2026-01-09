<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * statisticslevelotherenumcategory.
 */
enum StatisticsLevel: int
{
    /**
     * statisticslevelother:secondlevel.
     */
    case LEVEL_SECOND = 0;

    /**
     * statisticslevelother:minutesecondslevel.
     */
    case LEVEL_MINUTE = 1;

    /**
     * statisticslevelother:hourlevel.
     */
    case LEVEL_HOUR = 2;

    /**
     * statisticslevelother:daylevel.
     */
    case LEVEL_DAY = 3;

    /**
     * getstatisticslevelothername.
     */
    public function getName(): string
    {
        return match ($this) {
            self::LEVEL_SECOND => 'secondlevel',
            self::LEVEL_MINUTE => 'minutesecondslevel',
            self::LEVEL_HOUR => 'hourlevel',
            self::LEVEL_DAY => 'daylevel',
        };
    }

    /**
     * getstatisticslevelothername(staticstatemethod,useatcompatibleoldcode).
     * @deprecated useenuminstance getName() methodreplace
     */
    public static function getLevelName(int|self $level): string
    {
        if (is_int($level)) {
            return match ($level) {
                self::LEVEL_SECOND->value => 'secondlevel',
                self::LEVEL_MINUTE->value => 'minutesecondslevel',
                self::LEVEL_HOUR->value => 'hourlevel',
                self::LEVEL_DAY->value => 'daylevel',
                default => 'unknownlevelother',
            };
        }

        return $level->getName();
    }
}
