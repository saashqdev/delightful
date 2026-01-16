<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Config;

class WebSocketConfig 
{
 
    public function __construct( 
    private readonly int $maxRetries = 3, 
    private readonly int $retryDelay = 1, 
    private readonly int $maxIdleTime = 300, 
    private readonly int $connectTimeout = 3600, 
    private readonly int $readTimeout = 1800, 
    private readonly int $heartbeatInterval = 30, 
    private readonly string $heartbeatMessage = 'ping', 
    private readonly int $taskTimeout = 1800, // Default task timeout time 30 minutes 
    private readonly int $maxprocess ingTime = 3600, // Default maximum processing time 1 hour 
    private readonly int $pongTimeout = 10, // Pong frame timeout time (seconds) 
    private readonly int $maxRetryDelay = 30 // Maximum retry delay time (seconds) ) 
{
 
}
 
    public function getMaxRetries(): int 
{
 return $this->maxRetries; 
}
 
    public function getRetryDelay(): int 
{
 return $this->retryDelay; 
}
 
    public function getMaxIdleTime(): int 
{
 return $this->maxIdleTime; 
}
 
    public function getConnectTimeout(): int 
{
 return $this->connectTimeout; 
}
 
    public function getReadTimeout(): int 
{
 return $this->readTimeout; 
}
 
    public function getHeartbeatInterval(): int 
{
 return $this->heartbeatInterval; 
}
 
    public function getHeartbeatMessage(): string 
{
 return $this->heartbeatMessage; 
}
 /** * Get task processing timeout time (seconds) * When task execution time exceeds this value, will be considered timeout. */ 
    public function getTaskTimeout(): int 
{
 return $this->taskTimeout; 
}
 /** * Get maximum processing time (seconds) * When message processing loop execution time exceeds this value, will automatically terminate process. */ 
    public function getMaxprocess ingTime(): int 
{
 return $this->maxprocess ingTime; 
}
 /** * Get pong frame response timeout time (seconds) * After sending ping frame, if no pong response received within this time, connection is considered disconnected. */ 
    public function getPongTimeout(): int 
{
 return $this->pongTimeout; 
}
 /** * Get maximum retry delay time (seconds) * In exponential backoff retry policy, retry delay will not exceed this value. */ 
    public function getMaxRetryDelay(): int 
{
 return $this->maxRetryDelay; 
}
 
}
 
