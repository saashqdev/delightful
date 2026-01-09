<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\ValueObject;

enum TextPreprocessRule: int
{
    // 替换掉连续null格/换行符/制表符
    case REPLACE_WHITESPACE = 1;

    // delete所有url和电子邮件地址
    case REMOVE_URL_EMAIL = 2;

    // Exceltitle行拼接，剔除sheet行，行间换行调整为\n\n
    case FORMAT_EXCEL = 3;

    public function getDescription(): string
    {
        return match ($this) {
            self::REPLACE_WHITESPACE => '替换掉连续null格/换行符/制表符',
            self::REMOVE_URL_EMAIL => 'delete所有url和电子邮件地址',
            self::FORMAT_EXCEL => '剔除title行，将Excelcontent与title行拼接成"title:content"format，剔除sheet行，行间换行调整为\n\n',
        };
    }

    public static function fromArray(array $values): array
    {
        return array_map(fn ($value) => self::from($value), $values);
    }
}
