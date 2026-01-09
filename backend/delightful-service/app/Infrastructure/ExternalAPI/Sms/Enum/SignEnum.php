<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Enum;

enum SignEnum: string
{
    case DENG_TA = 'DengTa';

    public const array MAP = [
        self::DENG_TA->value => [
            LanguageEnum::ZH_CN->value => 'lighthouseengine',
        ],
        'lighthouseengine' => [
            LanguageEnum::ZH_CN->value => 'lighthouseengine',
        ],
    ];

    /**
     * shortmessagesignaturemultiplelanguageadapt.
     */
    public static function format(SignEnum $type, ?LanguageEnum $language, LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string
    {
        return self::MAP[$type->value][$language?->value] ?? (self::MAP[$type->value][$defaultLanguage->value] ?? $type->value);
    }
}
