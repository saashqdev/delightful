<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * MemberStatusValueObject * * MemberStatusValidate Rule */

enum MemberStatus: int 
{
 case INACTIVE = 0; case ACTIVE = 1; /** * whether as ActiveStatus */ 
    public function isActive(): bool 
{
 return $this === self::ACTIVE; 
}
 /** * whether as ActiveStatus */ 
    public function isInactive(): bool 
{
 return $this === self::INACTIVE; 
}
 /** * GetDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::ACTIVE => 'Active', self::INACTIVE => 'Inactive', 
}
; 
}
 
}
 
