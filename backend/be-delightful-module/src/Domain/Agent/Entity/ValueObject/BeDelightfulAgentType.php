<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

enum BeDelightfulAgentType: int 
{
 /** * Built-in. */ case Built_In = 1; /** * Custom. */ case Custom = 2; /** * GetTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::Built_In => 'Built-in', self::Custom => 'Custom', 
}
; 
}
 /** * whether as Built-inType. */ 
    public function isBuiltIn(): bool 
{
 return $this === self::Built_In; 
}
 /** * whether as CustomType. */ 
    public function isCustom(): bool 
{
 return $this === self::Custom; 
}
 /** * GetAllAvailableEnumValue. * @return array<int> */ 
    public 
    static function getAvailableValues(): array 
{
 return array_map(fn ($case) => $case->value, self::cases()); 
}
 /** * GetAllAvailableEnumValueStringfor Validate Rule. */ 
    public 
    static function getValidate Rule(): string 
{
 return implode(',', self::getAvailableValues()); 
}
 
}
 
