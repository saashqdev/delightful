<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Constant;

use InvalidArgumentException;
/** * topic CopyTaskStatusEnum. */

enum DuplicateStatusEnum: string 
{
 case RUNNING = 'running'; case FINISHED = 'finished'; case ERROR = 'error'; /** * GetAllValidStatusValue */ 
    public 
    static function getValidStatuses(): array 
{
 return array_map(fn (self $case) => $case->value, self::cases()); 
}
 /** * check Statuswhether valid. */ 
    public 
    static function isValid(string $status): bool 
{
 return in_array($status, self::getValidStatuses(), true); 
}
 /** * FromStringCreateEnumInstance. */ 
    public 
    static function fromString(string $status): self 
{
 return match ($status) 
{
 'running' => self::RUNNING, 'finished' => self::FINISHED, 'error' => self::ERROR, default => throw new InvalidArgumentException( Invalid status: 
{
$status
}
 ), 
}
; 
}
 
}
 
