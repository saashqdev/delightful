<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\ImageConvertHigh;

enum ImageConvertHighResponseType: int
{
    // startgenerate
    case START_GENERATE = 1;

    // generatecomplete
    case GENERATED = 2;

    // exceptiontermination
    case TERMINATE = 3;

    public static function getNameFromType(ImageConvertHighResponseType $type): string
    {
        return match ($type) {
            self::START_GENERATE => 'startgenerate',
            self::GENERATED => 'generatecomplete',
            self::TERMINATE => 'exceptiontermination',
            default => '未知type',
        };
    }
}
