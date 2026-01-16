<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

enum BuiltinAgent: string 
{
 /** Schema */ case General = 'general'; /** Schema */ case Chat = 'chat'; /** Data */ case DataAnalysis = 'data_analysis'; /** PPT */ case PPT = 'ppt'; /** Schema */ case Report = 'report'; /** recording summary */ case Summary = 'summary'; /** * GetBuilt-inName. */ 
    public function getName(): string 
{
 return match ($this) 
{
 self::General => 'Schema', self::Chat => 'Schema', self::DataAnalysis => 'Data', self::PPT => 'PPT', self::Report => 'Schema', self::Summary => 'recording summary ', 
}
; 
}
 /** * GetBuilt-inDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::General => 'for ', self::Chat => 'Pair', self::DataAnalysis => 'Dataprocess ', self::PPT => 'PPTDemo', self::Report => 'Professional research report writing assistant ', self::Summary => 'Content', 
}
; 
}
 /** * GetBuilt-inIcon. */ 
    public function getIcon(): array 
{
 return match ($this) 
{
 self::General => ['type' => 'general', 'color' => ''], self::Chat => ['type' => 'IconMessages', 'color' => ''], self::DataAnalysis => ['type' => 'IconChartBarPopular', 'color' => ''], self::PPT => ['type' => 'IconPresentation', 'color' => ''], self::Report => ['type' => 'report', 'color' => ''], self::Summary => ['type' => 'IconFileDescription', 'color' => ''], 
}
; 
}
 /** * GetBuilt-inNotice. */ 
    public function getPrompt(): array 
{
 return []; 
}
 /** * GetAllBuilt-in. * @return array<BuiltinAgent> */ 
    public 
    static function getAllBuiltinAgents(): array 
{
 return [ self::General, self::Chat, self::DataAnalysis, self::PPT, self::Summary, ]; 
}
 
}
 
