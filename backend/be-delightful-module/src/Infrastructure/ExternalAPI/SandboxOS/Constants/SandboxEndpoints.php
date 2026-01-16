<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants;

/** * sandbox API Constant * sandbox Service API Path. */

class SandboxEndpoints 
{
 /** * ASR Task. */ 
    public 
    const ASR_TASK_START = 'api/asr/task/start'; /** * ASR Taskcomplete . */ 
    public 
    const ASR_TASK_FINISH = 'api/asr/task/finish'; /** * ASR Taskcancel . */ 
    public 
    const ASR_TASK_CANCEL = 'api/asr/task/cancel'; /** * Agent Message. */ 
    public 
    const AGENT_MESSAGES_CHAT = 'api/v1/messages/chat'; 
}
 
