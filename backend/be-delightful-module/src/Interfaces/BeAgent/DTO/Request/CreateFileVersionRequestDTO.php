<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

/**
 * Create file version request DTO.
 */
class CreateFileVersionRequestDTO extends AbstractRequestDTO
{
    /**
     * File key.
     */
    protected string $fileKey = '';

    /**
     * Edit type.
     */
    protected int $editType = 1;

    public function getFileKey(): string
    {
        return $this->fileKey;
    }

    public function setFileKey(string $fileKey): void
    {
        $this->fileKey = $fileKey;
    }

    public function getEditType(): int
    {
        return $this->editType;
    }

    public function setEditType(int $editType): void
    {
        $this->editType = $editType;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'file_key' => 'required|string|max:500',
            'edit_type' => 'sometimes|integer|in:1,2',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'file_key.required' => 'File key cannot be empty',
            'file_key.string' => 'File key must be a string',
            'file_key.max' => 'File key cannot exceed 500 characters',
            'edit_type.integer' => 'Edit type must be an integer',
            'edit_type.in' => 'Edit type must be 1 (manual) or 2 (AI)',
        ];
    }
}
