<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use Exception;

enum BeDelightfulAgentOptimizationType: string 
{
 case None = 'none'; case OptimizeNameDescription = 'optimize_name_description'; case OptimizeContent = 'optimize_content'; case OptimizeName = 'optimize_name'; case OptimizeDescription = 'optimize_description'; /** * GetEnumDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::OptimizeNameDescription => 'optimize NameDescription', self::OptimizeContent => 'optimize Content', self::OptimizeName => 'optimize Name', self::OptimizeDescription => 'optimize Description', self::None => throw new Exception('To be implemented'), 
}
; 
}
 /** * GetAllEnumValue. */ 
    public 
    static function values(): array 
{
 return array_column(self::cases(), 'value'); 
}
 /** * FromStringCreateEnumInstance. */ 
    public 
    static function fromString(string $value): self 
{
 $type = self::tryFrom($value); if ($type === null) 
{
 return self::None; 
}
 return $type; 
}
 
    public function isNone(): bool 
{
 return $this === self::None; 
}
 
}
 
