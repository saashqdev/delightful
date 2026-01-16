<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
/** * InterruptRequestClass * Followsandbox DocumentationInterruptRequestFormat. */

class InterruptRequest 
{
 
    public function __construct( 
    private string $messageId = '', 
    private string $userId = '', 
    private string $taskId = '', 
    private string $remark = '' ) 
{
 
}
 /** * CreateInterruptRequest */ 
    public 
    static function create(string $messageId, string $userId, string $taskId, string $remark = ''): self 
{
 return new self($messageId, $userId, $taskId, $remark); 
}
 /** * Set user ID. */ 
    public function setuser Id(string $userId): self 
{
 $this->userId = $userId; return $this; 
}
 /** * Getuser ID. */ 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 /** * Set TaskID. */ 
    public function setTaskId(string $taskId): self 
{
 $this->taskId = $taskId; return $this; 
}
 /** * GetTaskID. */ 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 /** * Set MessageID. */ 
    public function setMessageId(string $messageId): self 
{
 $this->messageId = $messageId; return $this; 
}
 /** * GetMessageID. */ 
    public function getMessageId(): string 
{
 return $this->messageId; 
}
 /** * Set Remark. */ 
    public function setRemark(string $remark): self 
{
 $this->remark = $remark; return $this; 
}
 /** * GetRemark. */ 
    public function getRemark(): string 
{
 return $this->remark; 
}
 /** * Convert toAPIRequestArray * According tosandbox DocumentationInterruptRequestFormat. */ 
    public function toArray(): array 
{
 $data = [ 'message_id' => ! empty($this->messageId) ? $this->messageId : (string) IdGenerator::getSnowId(), 'user_id' => $this->userId, 'task_id' => $this->taskId, 'prompt' => '', 'type' => 'chat', 'context_type' => 'interrupt', ]; // IfHaveRemarkAddRequestin if (! empty($this->remark)) 
{
 $data['remark'] = $this->remark; 
}
 return $data; 
}
 
}
 
