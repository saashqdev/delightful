<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

use App\Infrastructure\Util\IdGenerator\IdGenerator;

/**
 * Interrupt request class
 * Follows the sandbox communication interrupt request format.
 */
class InterruptRequest
{
    public function __construct(
        private string $messageId = '',
        private string $userId = '',
        private string $taskId = '',
        private string $remark = ''
    ) {
    }

    /**
     * Create an interrupt request
     */
    public static function create(string $messageId, string $userId, string $taskId, string $remark = ''): self
    {
        return new self($messageId, $userId, $taskId, $remark);
    }

    /**
     * Set user ID.
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
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
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Set message ID.
     */
    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Set remark.
     */
    public function setRemark(string $remark): self
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * Get remark.
     */
    public function getRemark(): string
    {
        return $this->remark;
    }

    /**
    * Convert to API request array
    * Matches the sandbox communication interrupt request format.
    */
    public function toArray(): array
    {
        $data = [
            'message_id' => ! empty($this->messageId) ? $this->messageId : (string) IdGenerator::getSnowId(),
            'user_id' => $this->userId,
            'task_id' => $this->taskId,
            'prompt' => '',
            'type' => 'chat',
            'context_type' => 'interrupt',
        ];

        // Include remark when present
        if (! empty($this->remark)) {
            $data['remark'] = $this->remark;
        }

        return $data;
    }
}
