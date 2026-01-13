<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

class BingSearchMarketCode
{
    public const string ZH_CN = 'zh-CN';

    public const string EN_US = 'en-US';

    public const string TH_TH = 'th-TH';

    public const string ms_MY = 'ms-MY';

    public const string VI_VN = 'vi-VN';

    public static function fromLanguage(?string $language): string
    {
        return match ($language) {
            'en_US' => self::ZH_CN,
            'th_TH' => self::TH_TH,
            'ms_MY' => self::ms_MY,
            'vi_VN' => self::VI_VN,
            default => self::EN_US,
        };
    }
}
