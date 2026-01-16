<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * TaskEventEnum. */

enum TaskEvent: string 
{
 /** * TaskPending. */ case SUSPENDED = 'suspended'; /** * Taskterminate . */ case TERMINATED = 'terminated'; /** * GetEventDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::SUSPENDED => 'TaskPending', self::TERMINATED => 'Taskterminate ', 
}
; 
}
 /** * whether as PendingStatus */ 
    public function isSuspended(): bool 
{
 return $this === self::SUSPENDED; 
}
 /** * whether as terminate Status */ 
    public function isterminate d(): bool 
{
 return $this === self::TERMINATED; 
}
 
}
 
