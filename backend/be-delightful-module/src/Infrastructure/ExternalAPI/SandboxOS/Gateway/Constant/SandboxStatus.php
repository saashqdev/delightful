<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant;

/** * Sandbox statusConstant * According tosandbox DocumentationStatusValue */

class SandboxStatus 
{
 /** * sandbox Status */ 
    public 
    const PENDING = 'Pending'; /** * sandbox RunningStatus */ 
    public 
    const RUNNING = 'Running'; /** * sandbox ExitStatus */ 
    public 
    const EXITED = 'Exited'; /** * sandbox UnknownStatus */ 
    public 
    const UNKNOWN = 'Unknown'; /** * sandbox not found Status */ 
    public 
    const NOT_FOUND = 'NotFound'; /** * GetAllValidStatus */ 
    public 
    static function getAllStatuses(): array 
{
 return [ self::PENDING, self::RUNNING, self::EXITED, self::UNKNOWN, self::NOT_FOUND, ]; 
}
 /** * check Statuswhether valid. */ 
    public 
    static function isValidStatus(string $status): bool 
{
 return in_array($status, self::getAllStatuses(), true); 
}
 /** * check sandbox whether AvailableRunning. */ 
    public 
    static function isAvailable(string $status): bool 
{
 return $status === self::RUNNING; 
}
 
}
 
