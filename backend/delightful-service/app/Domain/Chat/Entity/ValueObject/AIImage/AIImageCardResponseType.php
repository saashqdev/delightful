<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AIImage;

enum AIImageCardResponseType: int
{
    // 开始generate
    case START_GENERATE = 1;

    // generatecomplete
    case GENERATED = 2;

    // quoteimage
    case REFERENCE_IMAGE = 3;

    // exception终止
    case TERMINATE = 4;

    public static function getNameFromType(AIImageCardResponseType $type): string
    {
        return match ($type) {
            self::START_GENERATE => '开始generate',
            self::GENERATED => 'generatecomplete',
            self::REFERENCE_IMAGE => 'quoteimage',
            default => '未知type',
        };
    }
}
