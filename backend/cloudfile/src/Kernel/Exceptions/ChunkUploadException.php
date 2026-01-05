<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Exceptions;

use Throwable;

/**
 * Chunk upload exception class.
 */
class ChunkUploadException extends CloudFileException
{
    /**
     * Upload ID.
     */
    private string $uploadId = '';

    /**
     * Part number.
     */
    private int $partNumber = 0;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, string $uploadId = '', int $partNumber = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->uploadId = $uploadId;
        $this->partNumber = $partNumber;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getPartNumber(): int
    {
        return $this->partNumber;
    }

    /**
     * Create init multipart upload failed exception.
     */
    public static function createInitFailed(string $message, string $uploadId = '', ?Throwable $previous = null): self
    {
        return new self("Init multipart upload failed: {$message}", 1001, $previous, $uploadId);
    }

    /**
     * Create part upload failed exception.
     */
    public static function createPartUploadFailed(string $message, string $uploadId, int $partNumber, ?Throwable $previous = null): self
    {
        return new self("Upload part {$partNumber} failed: {$message}", 1002, $previous, $uploadId, $partNumber);
    }

    /**
     * Create complete multipart upload failed exception.
     */
    public static function createCompleteFailed(string $message, string $uploadId, ?Throwable $previous = null): self
    {
        return new self("Complete multipart upload failed: {$message}", 1003, $previous, $uploadId);
    }

    /**
     * Create abort multipart upload failed exception.
     */
    public static function createAbortFailed(string $message, string $uploadId, ?Throwable $previous = null): self
    {
        return new self("Abort multipart upload failed: {$message}", 1004, $previous, $uploadId);
    }

    /**
     * Create invalid chunk size exception.
     */
    public static function createInvalidChunkSize(int $chunkSize): self
    {
        return new self("Invalid chunk size: {$chunkSize}. Must be between 5MB and 5GB", 1005);
    }

    /**
     * Create too many chunks exception.
     */
    public static function createTooManyChunks(int $chunkCount): self
    {
        return new self("Too many chunks: {$chunkCount}. Maximum allowed is 10000", 1006);
    }

    /**
     * Create retry exhausted exception.
     */
    public static function createRetryExhausted(string $uploadId, int $partNumber, int $maxRetries): self
    {
        return new self("Retry exhausted for part {$partNumber} after {$maxRetries} attempts", 1007, null, $uploadId, $partNumber);
    }

    /**
     * Create timeout exception.
     */
    public static function createTimeout(string $uploadId, int $partNumber = 0): self
    {
        $message = $partNumber > 0 ? "Upload part {$partNumber} timeout" : 'Upload timeout';
        return new self($message, 1008, null, $uploadId, $partNumber);
    }
}
