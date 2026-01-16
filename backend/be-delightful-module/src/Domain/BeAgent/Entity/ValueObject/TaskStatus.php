<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * TaskStatusValueObject */

enum TaskStatus: string 
{
 /** * Waiting. */ case WAITING = 'waiting'; /** * Running. */ case RUNNING = 'running'; /** * complete d. */ case FINISHED = 'finished'; /** * Pending. */ case Suspended = 'suspended'; /** * terminate . */ case Stopped = 'stopped'; /** * Error. */ case ERROR = 'error'; /** * GetStatusDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::WAITING => 'Waiting', self::RUNNING => 'Running', self::FINISHED => 'complete d', self::ERROR => 'Error', self::Suspended => 'Pending', self::Stopped => 'terminate ', 
}
; 
}
 /** * GetAllStatuslist . * * @return array<string, string> StatusValueDescriptionMap */ 
    public 
    static function getlist (): array 
{
 return [ self::WAITING->value => self::WAITING->getDescription(), self::RUNNING->value => self::RUNNING->getDescription(), self::FINISHED->value => self::FINISHED->getDescription(), self::ERROR->value => self::ERROR->getDescription(), self::Suspended->value => self::Suspended->getDescription(), self::Stopped->value => self::Stopped->getDescription(), ]; 
}
 /** * whether is in 
    final state */ 
    public function isFinal(): bool 
{
 return in_array($this, [self::FINISHED, self::ERROR, self::Stopped, self::Suspended], true); 
}
 /** * whether as active Status */ 
    public function isActive(): bool 
{
 return in_array($this, [self::WAITING, self::RUNNING], true); 
}
 
}
 
