<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\ValueObject;

enum TextPreprocessRule: int
{
    // 替换掉连续null格/换line符/制表符
    case REPLACE_WHITESPACE = 1;

    // delete所haveurl和电子邮itemground址
    case REMOVE_URL_EMAIL = 2;

    // Exceltitlelinesplice，剔exceptsheetline，linebetween换lineadjust为\n\n
    case FORMAT_EXCEL = 3;

    public function getDescription(): string
    {
        return match ($this) {
            self::REPLACE_WHITESPACE => '替换掉连续null格/换line符/制表符',
            self::REMOVE_URL_EMAIL => 'delete所haveurl和电子邮itemground址',
            self::FORMAT_EXCEL => '剔excepttitleline，将Excelcontent与titlelinesplicebecome"title:content"format，剔exceptsheetline，linebetween换lineadjust为\n\n',
        };
    }

    public static function fromArray(array $values): array
    {
        return array_map(fn ($value) => self::from($value), $values);
    }
}
