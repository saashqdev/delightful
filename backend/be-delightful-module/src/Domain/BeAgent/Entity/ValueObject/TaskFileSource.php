<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * TaskFileSourceEnum. */

enum TaskFileSource: int 
{
 case DEFAULT = 0; /** * First page. */ case HOME = 1; /** * ItemDirectory. */ case PROJECT_DIRECTORY = 2; /** * Agent. */ case AGENT = 3; case COPY = 4; /** * Move. */ case MOVE = 6; /** * GetSourceName. */ 
    public function getName(): string 
{
 return match ($this) 
{
 self::DEFAULT => 'Default', self::HOME => 'First page', self::PROJECT_DIRECTORY => 'ItemDirectory', self::AGENT => 'Agent', self::COPY => 'Copy', self::MOVE => 'Move', 
}
; 
}
 /** * FromStringor IntegerCreateEnumInstance. */ 
    public 
    static function fromValue(int|string $value): self 
{
 if (is_string($value)) 
{
 $value = (int) $value; 
}
 return match ($value) 
{
 1 => self::HOME, 2 => self::PROJECT_DIRECTORY, 3 => self::AGENT, 4 => self::COPY, 6 => self::MOVE, default => self::DEFAULT, 
}
; 
}
 
}
 
