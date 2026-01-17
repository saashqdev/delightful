<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

class CreateBatchDownloadResponseDTO
{
    /**
     * @var string Processing status (ready|processing)
     */
    protected string $status;

    /**
     * @var string Batch Key
     */
    protected string $batchKey;

    /**
     * @var null|string Download URL (provided when status is ready)
     */
    protected ?string $downloadUrl;

    /**
     * @var int File count
     */
    protected int $fileCount;

    /**
     * @var string Description message
     */
    protected string $message;

    /**
     * Constructor.
     */
    public function __construct(
        string $status,
        string $batchKey,
        ?string $downloadUrl = null,
        int $fileCount = 0,
        string $message = ''
    ) {
        $this->status = $status;
        $this->batchKey = $batchKey;
        $this->downloadUrl = $downloadUrl;
        $this->fileCount = $fileCount;
        $this->message = $message;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'batch_key' => $this->batchKey,
            'download_url' => $this->downloadUrl,
            'file_count' => $this->fileCount,
            'message' => $this->message,
        ];
    }

    /**
     * Get processing status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get batch Key.
     */
    public function getBatchKey(): string
    {
        return $this->batchKey;
    }

    /**
     * Get download URL.
     */
    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    /**
     * Get file count.
     */
    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    /**
     * Get description message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
