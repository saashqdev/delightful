<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AIImage;

enum AIImageCardResponseType: int
{
    // 开始生成
    case START_GENERATE = 1;

    // 生成完成
    case GENERATED = 2;

    // 引用image
    case REFERENCE_IMAGE = 3;

    // exception终止
    case TERMINATE = 4;

    public static function getNameFromType(AIImageCardResponseType $type): string
    {
        return match ($type) {
            self::START_GENERATE => '开始生成',
            self::GENERATED => '生成完成',
            self::REFERENCE_IMAGE => '引用image',
            default => '未知type',
        };
    }
}
