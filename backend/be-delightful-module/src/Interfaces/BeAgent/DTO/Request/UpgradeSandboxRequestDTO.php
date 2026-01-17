<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class UpgradeSandboxRequestDTO extends AbstractRequestDTO
{
    protected string $messageId = '';

    protected string $contextType = 'continue';

    /**
     * Validation rules.
     */
    public static function getHyperfValidationRules(): array
    {
        return [
            'message_id' => 'required|string',
            'context_type' => 'required|string|in:continue',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'message_id.required' => 'Message ID cannot be empty',
            'message_id.string' => 'Message ID must be a string',
            'context_type.required' => 'Context type cannot be empty',
            'context_type.string' => 'Context type must be a string',
            'context_type.in' => 'Context type can only be continue',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'message_id' => 'Message ID',
            'context_type' => 'Context type',
        ];
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function getContextType(): string
    {
        return $this->contextType;
    }

    public function setContextType(string $contextType): void
    {
        $this->contextType = $contextType;
    }
}
