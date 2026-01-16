<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant;

/** * workspace StatusConstant * Agentworkspace StatusValue. */

class WorkspaceStatus 
{
 /** * Initialize - AgentDispatcherCreateor Initialize. */ 
    public 
    const int UNINITIALIZED = 0; /** * AtInitialize - StatusUsing. */ 
    public 
    const int INITIALIZING = 1; /** * Initializecomplete - workspace FullAvailable. */ 
    public 
    const int READY = 2; /** * InitializeError - Initializein occurred Exception. */ 
    public 
    const int ERROR = -1; /** * GetStatusDescription. * * @param int $status StatusValue * @return string StatusDescription */ 
    public 
    static function getDescription(int $status): string 
{
 return match ($status) 
{
 self::UNINITIALIZED => 'Initialize', self::INITIALIZING => 'AtInitialize', self::READY => 'Initializecomplete ', self::ERROR => 'InitializeError', default => 'UnknownStatus', 
}
; 
}
 /** * check Statuswhether as Status. * * @param int $status StatusValue * @return bool whether */ 
    public 
    static function isReady(int $status): bool 
{
 return $status === self::READY; 
}
 /** * check Statuswhether as ErrorStatus. * * @param int $status StatusValue * @return bool whether Error */ 
    public 
    static function isError(int $status): bool 
{
 return $status === self::ERROR; 
}
 
}
 
