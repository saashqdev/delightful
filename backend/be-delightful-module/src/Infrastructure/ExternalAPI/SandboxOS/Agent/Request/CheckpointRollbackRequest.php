<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/**
 * Checkpoint rollback request class
 * Strictly follows the checkpoint rollback request format in the sandbox communication documentation.
 */
class CheckpointRollbackRequest
{
    public function __construct(
        private string $targetMessageId = '',
    ) {
    }

    /**
     * Create a checkpoint rollback request object
     */
    public static function create(
        string $targetMessageId,
    ): self {
        return new self($targetMessageId);
    }

    /**
     * Get target message ID.
     */
    public function getTargetMessageId(): string
    {
        return $this->targetMessageId;
    }

    /**
     * Set target message ID.
     */
    public function setTargetMessageId(string $targetMessageId): self
    {
        $this->targetMessageId = $targetMessageId;
        return $this;
    }

    /**
     * Convert to API request array
     * According to checkpoint rollback request format in sandbox communication documentation.
     */
    public function toArray(): array
    {
        return [
            'target_message_id' => $this->targetMessageId,
        ];
    }
}
