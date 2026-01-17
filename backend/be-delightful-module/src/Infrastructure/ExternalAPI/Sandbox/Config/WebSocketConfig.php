<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

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
        private readonly int $taskTimeout = 1800,      // Default task timeout 30 minutes
        private readonly int $maxProcessingTime = 3600, // Default max processing time 1 hour
        private readonly int $pongTimeout = 10,       // Pong frame timeout (seconds)
        private readonly int $maxRetryDelay = 30      // Max retry delay time (seconds)
    ) {
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

    /**
     * Get task processing timeout time (seconds)
     * When task execution time exceeds this value, it will be considered a timeout.
     */
    public function getTaskTimeout(): int
    {
        return $this->taskTimeout;
    }

    /**
     * Get max processing time (seconds)
     * When message processing loop execution time exceeds this value, processing will be automatically terminated.
     */
    public function getMaxProcessingTime(): int
    {
        return $this->maxProcessingTime;
    }

    /**
     * Get Pong frame response timeout time (seconds)
     * After sending Ping frame, if Pong response is not received within this time, the connection is considered disconnected.
     */
    public function getPongTimeout(): int
    {
        return $this->pongTimeout;
    }

    /**
     * Get max retry delay time (seconds)
     * In exponential backoff retry strategy, retry delay will not exceed this value.
     */
    public function getMaxRetryDelay(): int
    {
        return $this->maxRetryDelay;
    }
}
