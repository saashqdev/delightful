<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/**
 * Project Mode Value Object
 * 项目模式值对象
 */
enum ProjectMode: string
{
    case GENERAL = 'general';           // 通用模式
    case PPT = 'ppt';                  // PPT模式
    case DATA_ANALYSIS = 'data_analysis'; // 数据分析模式
    case REPORT = 'report';            // 研报模式
    case MEETING = 'meeting';          // 会议模式
    case SUMMARY = 'summary';          // 总结模式
    case BE_DELIGHTFUL = 'super_magic';  // 超级麦吉模式

    /**
     * Get all available project modes.
     */
    public static function getAllModes(): array
    {
        return [
            self::GENERAL->value,
            self::PPT->value,
            self::DATA_ANALYSIS->value,
            self::REPORT->value,
            self::MEETING->value,
            self::SUMMARY->value,
            self::BE_DELIGHTFUL->value,
        ];
    }

    /**
     * Get project mode description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GENERAL => '通用模式',
            self::PPT => 'PPT模式',
            self::DATA_ANALYSIS => '数据分析模式',
            self::REPORT => '研报模式',
            self::MEETING => '会议模式',
            self::SUMMARY => '总结模式',
            self::BE_DELIGHTFUL => '超级麦吉模式',
        };
    }
}
