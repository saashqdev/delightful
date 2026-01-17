<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;

class CreateTaskResponseDTO
{
    /**
     * @var string Task ID
     */
    protected string $taskId;

    /**
     * @var string Task status
     */
    protected string $status;

    /**
     * @var string Creation time
     */
    protected string $createdAt;

    /**
     * Constructor.
     */
    public function __construct(int $taskId, string $status, string $createdAt)
    {
        $this->taskId = (string) $taskId;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    /**
     * Create response DTO from task entity.
     */
    public static function fromEntity(TaskEntity $entity): self
    {
        return new self(
            $entity->getId(),
            $entity->getStatus()->value,
            $entity->getCreatedAt()
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Get task status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get creation time.
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
