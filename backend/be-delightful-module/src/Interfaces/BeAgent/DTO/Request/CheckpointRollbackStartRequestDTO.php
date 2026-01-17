<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class CheckpointRollbackStartRequestDTO extends AbstractRequestDTO
{
    protected string $targetMessageId = '';

    /**
     * Validation rules.
     */
    public static function getHyperfValidationRules(): array
    {
        return [
            'target_message_id' => 'required|string',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'target_message_id.required' => 'Target message ID cannot be empty',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'target_message_id' => 'Target message ID',
        ];
    }

    public function getTargetMessageId(): string
    {
        return $this->targetMessageId;
    }

    public function setTargetMessageId(string $targetMessageId): void
    {
        $this->targetMessageId = $targetMessageId;
    }
}
