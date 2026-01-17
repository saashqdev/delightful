<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/**
 * Checkpoint rollback commit request class
 * Strictly follows the checkpoint rollback commit request format in the sandbox communication documentation.
 */
class CheckpointRollbackCommitRequest
{
    /**
     * Create a checkpoint rollback commit request object
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Convert to API request array
     * According to checkpoint rollback commit request format in sandbox communication documentation (empty request body).
     */
    public function toArray(): array
    {
        return [];
    }
}
