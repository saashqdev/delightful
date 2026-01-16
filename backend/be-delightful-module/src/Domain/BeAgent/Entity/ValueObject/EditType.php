<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * EditTypeEnum. */

enum EditType: int 
{
 /** * Edit. */ case MANUAL = 1; /** * AIEdit. */ case AI = 2; /** * GetEditTypeName. */ 
    public function getName(): string 
{
 return match ($this) 
{
 self::MANUAL => 'Edit', self::AI => 'AIEdit', 
}
; 
}
 /** * GetEditTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::MANUAL => 'ManualEditVersion', self::AI => 'AIautomatic EditVersion', 
}
; 
}
 
}
 
