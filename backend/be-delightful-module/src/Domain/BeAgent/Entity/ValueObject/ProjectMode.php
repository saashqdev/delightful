<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Project Mode Value Object
 * Project mode value object
 */
enum ProjectMode: string
{
    case GENERAL = 'general';           // General mode
    case PPT = 'ppt';                  // PPT mode
    case DATA_ANALYSIS = 'data_analysis'; // Data analysis mode
    case REPORT = 'report';            // Research report mode
    case MEETING = 'meeting';          // Meeting mode
    case SUMMARY = 'summary';          // Summary mode
    case BE_DELIGHTFUL = 'be_delightful';  // Super Magi mode

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
            self::GENERAL => 'General mode',
            self::PPT => 'PPT mode',
            self::DATA_ANALYSIS => 'Data analysis mode',
            self::REPORT => 'Research report mode',
            self::MEETING => 'Meeting mode',
            self::SUMMARY => 'Summary mode',
            self::BE_DELIGHTFUL => 'Super Magi mode',
        };
    }
}
