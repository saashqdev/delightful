<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * Project Mode Value Object * ItemSchemaValueObject */

enum ProjectMode: string 
{
 case GENERAL = 'general'; // Schema case PPT = 'ppt'; // PPTSchema case DATA_ANALYSIS = 'data_analysis'; // DataSchema case REPORT = 'report'; // Schema case MEETING = 'meeting'; // Schema case SUMMARY = 'summary'; // Schema case SUPER_MAGIC = 'super_magic'; // Super MaggieSchema /** * Get all available project modes. */ 
    public 
    static function getAllModes(): array 
{
 return [ self::GENERAL->value, self::PPT->value, self::DATA_ANALYSIS->value, self::REPORT->value, self::MEETING->value, self::SUMMARY->value, self::SUPER_MAGIC->value, ]; 
}
 /** * Get project mode description. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::GENERAL => 'Schema', self::PPT => 'PPTSchema', self::DATA_ANALYSIS => 'DataSchema', self::REPORT => 'Schema', self::MEETING => 'Schema', self::SUMMARY => 'Schema', self::SUPER_MAGIC => 'Super MaggieSchema', 
}
; 
}
 
}
 
