<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

class StatusMetricsDTO extends AbstractDTO 
{
 /** * @var int ErrorStatustopic Quantity */ 
    protected int $errorCount = 0; /** * @var int complete Statustopic Quantity */ 
    protected int $completedCount = 0; /** * @var int RunningStatustopic Quantity */ 
    protected int $runningCount = 0; /** * @var int WaitingStatustopic Quantity */ 
    protected int $waitingCount = 0; /** * @var int PausedStatustopic Quantity */ 
    protected int $pausedCount = 0; /** * GetErrorStatustopic Quantity. */ 
    public function getErrorCount(): int 
{
 return $this->errorCount; 
}
 /** * Set ErrorStatustopic Quantity. */ 
    public function setErrorCount(int $errorCount): self 
{
 $this->errorCount = $errorCount; return $this; 
}
 /** * Getcomplete Statustopic Quantity. */ 
    public function getcomplete dCount(): int 
{
 return $this->completedCount; 
}
 /** * Set complete Statustopic Quantity. */ 
    public function setcomplete dCount(int $completedCount): self 
{
 $this->completedCount = $completedCount; return $this; 
}
 /** * GetRunningStatustopic Quantity. */ 
    public function getRunningCount(): int 
{
 return $this->runningCount; 
}
 /** * Set RunningStatustopic Quantity. */ 
    public function setRunningCount(int $runningCount): self 
{
 $this->runningCount = $runningCount; return $this; 
}
 /** * GetWaitingStatustopic Quantity. */ 
    public function getWaitingCount(): int 
{
 return $this->waitingCount; 
}
 /** * Set WaitingStatustopic Quantity. */ 
    public function setWaitingCount(int $waitingCount): self 
{
 $this->waitingCount = $waitingCount; return $this; 
}
 /** * GetPausedStatustopic Quantity. */ 
    public function getPausedCount(): int 
{
 return $this->pausedCount; 
}
 /** * Set PausedStatustopic Quantity. */ 
    public function setPausedCount(int $pausedCount): self 
{
 $this->pausedCount = $pausedCount; return $this; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'error_count' => $this->errorCount, 'completed_count' => $this->completedCount, 'running_count' => $this->runningCount, 'waiting_count' => $this->waitingCount, 'paused_count' => $this->pausedCount, ]; 
}
 
}
 
