<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use App\Application\Speech\Enum\SandboxAsrStatusEnum;

/**
 * ASR 沙箱合并结果 DTO.
 */
readonly class AsrSandboxMergeResultDTO
{
    public function __construct(
        public SandboxAsrStatusEnum $status,
        public string $filePath,
        public ?int $duration = null,
        public ?int $fileSize = null,
        public ?string $errorMessage = null
    ) {
    }

    /**
     * 从沙箱 API responsecreate DTO.
     */
    public static function fromSandboxResponse(array $response): self
    {
        $statusValue = $response['status'] ?? 'error';
        $status = SandboxAsrStatusEnum::fromString($statusValue) ?? SandboxAsrStatusEnum::ERROR;

        return new self(
            status: $status,
            filePath: $response['file_path'] ?? '',
            duration: $response['duration'] ?? null,
            fileSize: $response['file_size'] ?? null,
            errorMessage: $response['error_message'] ?? null
        );
    }

    /**
     * check合并是否complete.
     */
    public function isFinished(): bool
    {
        return $this->status->isCompleted();
    }

    /**
     * check合并是否failed.
     */
    public function isError(): bool
    {
        return $this->status->isError();
    }

    /**
     * 转换为array（用于兼容现有代码）.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'file_path' => $this->filePath,
            'duration' => $this->duration,
            'file_size' => $this->fileSize,
            'error_message' => $this->errorMessage,
        ];
    }
}
