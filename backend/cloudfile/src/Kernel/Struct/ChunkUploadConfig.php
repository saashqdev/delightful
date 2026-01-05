<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Struct;

use InvalidArgumentException;

/**
 * Chunk upload configuration class.
 */
class ChunkUploadConfig
{
    /**
     * Chunk size (bytes)
     * Default 10MB.
     */
    private int $chunkSize;

    /**
     * Chunk upload threshold (bytes)
     * Use chunk upload when file size exceeds this value
     * Default 20MB.
     */
    private int $threshold;

    /**
     * Maximum number of concurrent chunks
     * Default 3.
     */
    private int $maxConcurrency;

    /**
     * Maximum retry count
     * Default 3 times
     */
    private int $maxRetries;

    /**
     * Retry delay (milliseconds)
     * Default 1000ms.
     */
    private int $retryDelay;

    /**
     * Timeout (seconds)
     * Default 300 seconds (5 minutes).
     */
    private int $timeout;

    public function __construct(
        int $chunkSize = 10 * 1024 * 1024,  // 10MB
        int $threshold = 20 * 1024 * 1024, // 20MB
        int $maxConcurrency = 3,
        int $maxRetries = 3,
        int $retryDelay = 1000,
        int $timeout = 300
    ) {
        $this->setChunkSize($chunkSize);
        $this->setThreshold($threshold);
        $this->setMaxConcurrency($maxConcurrency);
        $this->setMaxRetries($maxRetries);
        $this->setRetryDelay($retryDelay);
        $this->setTimeout($timeout);
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function setChunkSize(int $chunkSize): void
    {
        if ($chunkSize < 5 * 1024 * 1024) { // Minimum 5MB
            throw new InvalidArgumentException('Chunk size must be at least 5MB');
        }
        if ($chunkSize > 1 * 1024 * 1024 * 1024) { // Maximum 1GB
            throw new InvalidArgumentException('Chunk size must not exceed 1GB');
        }
        $this->chunkSize = $chunkSize;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): void
    {
        if ($threshold < 0) {
            throw new InvalidArgumentException('Threshold must be non-negative');
        }
        $this->threshold = $threshold;
    }

    public function getMaxConcurrency(): int
    {
        return $this->maxConcurrency;
    }

    public function setMaxConcurrency(int $maxConcurrency): void
    {
        if ($maxConcurrency < 1) {
            throw new InvalidArgumentException('Max concurrency must be at least 1');
        }
        if ($maxConcurrency > 10) {
            throw new InvalidArgumentException('Max concurrency should not exceed 10');
        }
        $this->maxConcurrency = $maxConcurrency;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('Max retries must be non-negative');
        }
        $this->maxRetries = $maxRetries;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    public function setRetryDelay(int $retryDelay): void
    {
        if ($retryDelay < 0) {
            throw new InvalidArgumentException('Retry delay must be non-negative');
        }
        $this->retryDelay = $retryDelay;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        if ($timeout < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second');
        }
        $this->timeout = $timeout;
    }

    /**
     * Create default configuration.
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Create from configuration array.
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config['chunk_size'] ?? 10 * 1024 * 1024,
            $config['threshold'] ?? 20 * 1024 * 1024,
            $config['max_concurrency'] ?? 1,
            $config['max_retries'] ?? 3,
            $config['retry_delay'] ?? 1000,
            $config['timeout'] ?? 300
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'chunk_size' => $this->chunkSize,
            'threshold' => $this->threshold,
            'max_concurrency' => $this->maxConcurrency,
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'timeout' => $this->timeout,
        ];
    }
}
