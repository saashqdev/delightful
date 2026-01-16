<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * ItemStatusEnum. */

enum ProjectStatus: int 
{
 /** * active Status */ case ACTIVE = 1; /** * Archived. */ case ARCHIVED = 2; /** * delete d. */ case DELETED = 3; /** * GetStatusDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::ACTIVE => 'active ', self::ARCHIVED => 'Archived', self::DELETED => 'delete d', 
}
; 
}
 /** * whether as active Status */ 
    public function isActive(): bool 
{
 return $this === self::ACTIVE; 
}
 /** * whether Archived. */ 
    public function isArchived(): bool 
{
 return $this === self::ARCHIVED; 
}
 /** * whether delete d. */ 
    public function isdelete d(): bool 
{
 return $this === self::DELETED; 
}
 
}
 
