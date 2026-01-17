<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response;

/**
 * ASR recording service response.
 */
class AsrRecorderResponse
{
    public int $code {
        get {
            return $this->code;
        }
    }

    public string $message {
        get {
            return $this->message;
        }
    }

    private array $data {
        get {
            return $this->data;
        }
    }

    public function __construct(int $code, string $message, array $data)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Create a response from the sandbox gateway result.
     */
    public static function fromGatewayResult(mixed $result): self
    {
        if (! $result->isSuccess()) {
            return new self(
                $result->getCode(),
                $result->getMessage(),
                []
            );
        }

        $data = $result->getData();
        return new self(
            $result->getCode(),
            $result->getMessage(),
            $data
        );
    }

    /**
     * Create from API response.
     */
    public static function fromApiResponse(array $response): self
    {
        return new self(
            $response['code'] ?? -1,
            $response['message'] ?? '',
            $response['data'] ?? []
        );
    }

    /**
     * Check success (code = 1000).
     */
    public function isSuccess(): bool
    {
        return $this->code === 1000;
    }

    /**
     * Get task status.
     */
    public function getStatus(): string
    {
        return $this->data['status'] ?? 'error';
    }

    /**
     * Get file path (compatible with V2 and legacy formats).
     */
    public function getFilePath(): ?string
    {
        // V2 format: read from files.audio_file.path
        if (isset($this->data['files']['audio_file']['path'])) {
            return $this->data['files']['audio_file']['path'];
        }

        // Legacy format: read from file_path (backward compatible)
        $path = $this->data['file_path'] ?? null;
        return $path !== '' ? $path : null;
    }

    /**
     * Get audio duration in seconds (compatible with V2 and legacy formats).
     */
    public function getDuration(): ?int
    {
        // V2 format: read from files.audio_file.duration
        if (isset($this->data['files']['audio_file']['duration'])) {
            return (int) $this->data['files']['audio_file']['duration'];
        }

        // Legacy format: read from duration (backward compatible)
        return $this->data['duration'] ?? null;
    }

    /**
     * Get file size in bytes (compatible with V2 and legacy formats).
     */
    public function getFileSize(): ?int
    {
        // V2 format: read from files.audio_file.size
        if (isset($this->data['files']['audio_file']['size'])) {
            return (int) $this->data['files']['audio_file']['size'];
        }

        // Legacy format: read from file_size (backward compatible)
        return $this->data['file_size'] ?? null;
    }

    /**
     * Get error message.
     */
    public function getErrorMessage(): ?string
    {
        return $this->data['error_message'] ?? null;
    }

    /**
     * Get full data array (for response handling).
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
