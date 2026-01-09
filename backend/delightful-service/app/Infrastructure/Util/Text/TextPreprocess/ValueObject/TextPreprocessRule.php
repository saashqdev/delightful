<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\ValueObject;

enum TextPreprocessRule: int
{
    // replace掉continuousnull格/换line符/tab
    case REPLACE_WHITESPACE = 1;

    // delete所haveurlandemailitemground址
    case REMOVE_URL_EMAIL = 2;

    // Exceltitlelinesplice,剔exceptsheetline,linebetween换lineadjustfor\n\n
    case FORMAT_EXCEL = 3;

    public function getDescription(): string
    {
        return match ($this) {
            self::REPLACE_WHITESPACE => 'replace掉continuousnull格/换line符/tab',
            self::REMOVE_URL_EMAIL => 'delete所haveurlandemailitemground址',
            self::FORMAT_EXCEL => '剔excepttitleline,willExcelcontentandtitlelinesplicebecome"title:content"format,剔exceptsheetline,linebetween换lineadjustfor\n\n',
        };
    }

    public static function fromArray(array $values): array
    {
        return array_map(fn ($value) => self::from($value), $values);
    }
}
