<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/**
 * Checkpoint rollback undo request class
 * Strictly follows the checkpoint rollback undo request format in the sandbox communication documentation.
 */
class CheckpointRollbackUndoRequest
{
    /**
     * Create a checkpoint rollback undo request object
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Convert to API request array
     * According to checkpoint rollback undo request format in sandbox communication documentation (empty request body).
     */
    public function toArray(): array
    {
        return [];
    }
}
