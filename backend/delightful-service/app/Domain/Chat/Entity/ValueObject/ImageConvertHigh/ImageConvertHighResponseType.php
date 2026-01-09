<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\ImageConvertHigh;

enum ImageConvertHighResponseType: int
{
    // 开始generate
    case START_GENERATE = 1;

    // generatecomplete
    case GENERATED = 2;

    // exception终止
    case TERMINATE = 3;

    public static function getNameFromType(ImageConvertHighResponseType $type): string
    {
        return match ($type) {
            self::START_GENERATE => '开始generate',
            self::GENERATED => 'generatecomplete',
            self::TERMINATE => 'exception终止',
            default => '未知type',
        };
    }
}
