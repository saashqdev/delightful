<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

class CheckBatchDownloadResponseDTO
{
    /**
     * @var string Processing status (ready|processing|failed)
     */
    protected string $status;

    /**
     * @var null|string Download URL (provided when status is ready)
     */
    protected ?string $downloadUrl;

    /**
     * @var null|int Processing progress (0-100)
     */
    protected ?int $progress;

    /**
     * @var string Description message
     */
    protected string $message;

    /**
     * Constructor.
     */
    public function __construct(
        string $status,
        ?string $downloadUrl = null,
        ?int $progress = null,
        string $message = ''
    ) {
        $this->status = $status;
        $this->downloadUrl = $downloadUrl;
        $this->progress = $progress;
        $this->message = $message;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'download_url' => $this->downloadUrl,
            'progress' => $this->progress,
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
     * Get download URL.
     */
    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    /**
     * Get processing progress.
     */
    public function getProgress(): ?int
    {
        return $this->progress;
    }

    /**
     * Get description message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
