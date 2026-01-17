<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class CheckpointRollbackResponseDTO
{
    protected string $targetMessageId = '';

    protected string $message = '';

    /**
     * 构造函数.
     */
    public function __construct()
    {
    }

    public function getTargetMessageId(): string
    {
        return $this->targetMessageId;
    }

    public function setTargetMessageId(string $targetMessageId): void
    {
        $this->targetMessageId = $targetMessageId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'target_message_id' => $this->targetMessageId,
            'message' => $this->message,
        ];
    }
}
