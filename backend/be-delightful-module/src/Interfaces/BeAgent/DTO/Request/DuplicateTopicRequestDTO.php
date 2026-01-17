<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

/**
 * Duplicate topic request DTO.
 */
class DuplicateTopicRequestDTO extends AbstractRequestDTO
{
    /**
     * Target message ID.
     */
    public string $targetMessageId = '';

    /**
     * New topic name.
     */
    public string $newTopicName = '';

    /**
     * Get target message ID.
     */
    public function getTargetMessageId(): string
    {
        return $this->targetMessageId;
    }

    /**
     * Get new topic name.
     */
    public function getNewTopicName(): string
    {
        return $this->newTopicName;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'target_message_id' => 'required|string',
            'new_topic_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'target_message_id.required' => 'Target message ID is required',
            'target_message_id.string' => 'Target message ID must be a string',
            'new_topic_name.required' => 'New topic name is required',
            'new_topic_name.string' => 'New topic name must be a string',
            'new_topic_name.max' => 'New topic name cannot exceed 255 characters',
        ];
    }
}
