<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

/**
 * File conversion status response DTO.
 */
class FileConvertStatusResponseDTO
{
    /**
     * Conversion status
     */
    protected string $status;

    /**
     * Download link.
     */
    protected ?string $downloadUrl;

    /**
     * Progress percentage.
     */
    protected ?int $progress;

    /**
     * Status message.
     */
    protected string $message;

    /**
     * Total file count.
     */
    protected ?int $totalFiles;

    /**
     * Successful conversion count.
     */
    protected ?int $successCount;

    /**
     * Conversion type.
     */
    protected ?string $convertType;

    /**
     * Batch ID.
     */
    protected ?string $batchId;

    /**
     * Task key.
     */
    protected ?string $taskKey;

    /**
     * Conversion success rate.
     */
    protected ?float $conversionRate;

    /**
     * Constructor.
     */
    public function __construct(
        string $status,
        ?string $downloadUrl = null,
        ?int $progress = null,
        string $message = '',
        ?int $totalFiles = null,
        ?int $successCount = null,
        ?string $convertType = null,
        ?string $batchId = null,
        ?string $taskKey = null,
        ?float $conversionRate = null
    ) {
        $this->status = $status;
        $this->downloadUrl = $downloadUrl;
        $this->progress = $progress;
        $this->message = $message;
        $this->totalFiles = $totalFiles;
        $this->successCount = $successCount;
        $this->convertType = $convertType;
        $this->batchId = $batchId;
        $this->taskKey = $taskKey;
        $this->conversionRate = $conversionRate;
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['status'] ?? '',
            $data['download_url'] ?? null,
            $data['progress'] ?? null,
            $data['message'] ?? '',
            $data['total_files'] ?? null,
            $data['success_count'] ?? null,
            $data['convert_type'] ?? null,
            $data['batch_id'] ?? null,
            $data['task_key'] ?? null,
            $data['conversion_rate'] ?? null
        );
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
            'total_files' => $this->totalFiles,
            'success_count' => $this->successCount,
            'convert_type' => $this->convertType,
            'batch_id' => $this->batchId,
            'task_key' => $this->taskKey,
            'conversion_rate' => $this->conversionRate,
        ];
    }

    /**
     * Get conversion status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set conversion status
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get download link.
     */
    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    /**
     * Set download link.
     */
    public function setDownloadUrl(?string $downloadUrl): self
    {
        $this->downloadUrl = $downloadUrl;
        return $this;
    }

    /**
     * Get progress percentage.
     */
    public function getProgress(): ?int
    {
        return $this->progress;
    }

    /**
     * Set progress percentage.
     */
    public function setProgress(?int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * Get status message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set status message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get total file count.
     */
    public function getTotalFiles(): ?int
    {
        return $this->totalFiles;
    }

    /**
     * Set total file count.
     */
    public function setTotalFiles(?int $totalFiles): self
    {
        $this->totalFiles = $totalFiles;
        return $this;
    }

    /**
     * Get successful conversion count.
     */
    public function getSuccessCount(): ?int
    {
        return $this->successCount;
    }

    /**
     * Set successful conversion count.
     */
    public function setSuccessCount(?int $successCount): self
    {
        $this->successCount = $successCount;
        return $this;
    }

    /**
     * Get conversion type.
     */
    public function getConvertType(): ?string
    {
        return $this->convertType;
    }

    /**
     * Set conversion type.
     */
    public function setConvertType(?string $convertType): self
    {
        $this->convertType = $convertType;
        return $this;
    }

    /**
     * Get batch ID.
     */
    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    /**
     * Set batch ID.
     */
    public function setBatchId(?string $batchId): self
    {
        $this->batchId = $batchId;
        return $this;
    }

    /**
     * Get task key.
     */
    public function getTaskKey(): ?string
    {
        return $this->taskKey;
    }

    /**
     * Set task key.
     */
    public function setTaskKey(?string $taskKey): self
    {
        $this->taskKey = $taskKey;
        return $this;
    }

    /**
     * Get conversion success rate.
     */
    public function getConversionRate(): ?float
    {
        return $this->conversionRate;
    }

    /**
     * Set conversion success rate.
     */
    public function setConversionRate(?float $conversionRate): self
    {
        $this->conversionRate = $conversionRate;
        return $this;
    }
}
