<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\ValueObject;

enum TextPreprocessRule: int
{
    // replacedropcontinuousnullformat/exchangelinesymbol/tab
    case REPLACE_WHITESPACE = 1;

    // delete haveurlandemailitemgroundaddress
    case REMOVE_URL_EMAIL = 2;

    // Exceltitlelinesplice,剔exceptsheetline,linebetweenexchangelineadjustfor\n\n
    case FORMAT_EXCEL = 3;

    public function getDescription(): string
    {
        return match ($this) {
            self::REPLACE_WHITESPACE => 'replacedropcontinuousnullformat/exchangelinesymbol/tab',
            self::REMOVE_URL_EMAIL => 'delete haveurlandemailitemgroundaddress',
            self::FORMAT_EXCEL => '剔excepttitleline,willExcelcontentandtitlelinesplicebecome"title:content"format,剔exceptsheetline,linebetweenexchangelineadjustfor\n\n',
        };
    }

    public static function fromArray(array $values): array
    {
        return array_map(fn ($value) => self::from($value), $values);
    }
}
