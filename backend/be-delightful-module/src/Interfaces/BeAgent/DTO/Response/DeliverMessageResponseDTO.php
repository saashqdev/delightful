<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

class DeliverMessageResponseDTO
{
    /**
     * @param bool $success Whether successful
     * @param string $messageId Message ID
     */
    public function __construct(
        private bool $success,
        private string $messageId
    ) {
    }

    /**
     * Create response DTO from operation result.
     */
    public static function fromResult(bool $success, string $messageId = ''): self
    {
        return new self($success, $messageId);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message_id' => $this->messageId,
        ];
    }

    /**
     * Check if operation was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }
}
