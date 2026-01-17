<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

/**
 * Topic duplication status response DTO.
 */
class DuplicateTopicStatusResponseDTO
{
    /**
     * Task ID.
     */
    protected string $taskId;

    /**
     * Task status (running, completed, failed).
     */
    protected string $status;

    /**
     * Status message.
     */
    protected string $message;

    /**
     * Progress information.
     */
    protected ?array $progress = null;

    /**
     * Result information (when task is completed).
     */
    protected ?array $result = null;

    /**
     * Error information (when task fails).
     */
    protected ?string $error = null;

    /**
     * Constructor.
     */
    public function __construct(
        string $taskId,
        string $status,
        string $message = '',
        ?array $progress = null,
        ?array $result = null,
        ?string $error = null
    ) {
        $this->taskId = $taskId;
        $this->status = $status;
        $this->message = $message;
        $this->progress = $progress;
        $this->result = $result;
        $this->error = $error;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $result = [
            'task_id' => $this->taskId,
            'status' => $this->status,
            'message' => $this->message,
        ];

        if ($this->progress !== null) {
            $result['progress'] = $this->progress;
        }

        if ($this->result !== null) {
            $result['result'] = $this->result;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }

    /**
     * Create DTO instance from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['task_id'],
            $data['status'],
            $data['message'] ?? '',
            $data['progress'] ?? null,
            $data['result'] ?? null,
            $data['error'] ?? null
        );
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Set task ID.
     */
    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    /**
     * Get task status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set task status
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
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
     * Get progress information.
     */
    public function getProgress(): ?array
    {
        return $this->progress;
    }

    /**
     * Set progress information.
     */
    public function setProgress(?array $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * Get result information.
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * Set result information.
     */
    public function setResult(?array $result): self
    {
        $this->result = $result;
        return $this;
    }

    /**
     * Get error information.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Set error information.
     */
    public function setError(?string $error): self
    {
        $this->error = $error;
        return $this;
    }
}
