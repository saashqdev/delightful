<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\ImageConvertHigh;

enum ImageConvertHighResponseType: int
{
    // 开始生成
    case START_GENERATE = 1;

    // 生成complete
    case GENERATED = 2;

    // exception终止
    case TERMINATE = 3;

    public static function getNameFromType(ImageConvertHighResponseType $type): string
    {
        return match ($type) {
            self::START_GENERATE => '开始生成',
            self::GENERATED => '生成complete',
            self::TERMINATE => 'exception终止',
            default => '未知类型',
        };
    }
}
