<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

use App\ErrorCode\EventErrorCode;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskEvent;

class TaskEventUtil 
{
 
    public 
    static function getRemindTaskEventByCode(int $code): string 
{
 switch ($code) 
{
 case EventErrorCode::EVENT_TASK_PENDING: return TaskEvent::SUSPENDED->value; case EventErrorCode::EVENT_TASK_STOP: return TaskEvent::TERMINATED->value; default: return ''; 
}
 
}
 
}
 
