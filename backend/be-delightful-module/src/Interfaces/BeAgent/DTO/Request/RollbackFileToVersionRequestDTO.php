<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

/**
 * Rollback file to specified version request DTO.
 */
class RollbackFileToVersionRequestDTO extends AbstractRequestDTO
{
    /**
     * File ID (obtained from route parameter).
     */
    protected int $fileId = 0;

    /**
     * Target version number.
     */
    protected int $version = 0;

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function setFileId(int|string $value): void
    {
        $this->fileId = (int) $value;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int|string $value): void
    {
        $this->version = (int) $value;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'file_id' => 'required|integer|min:1',
            'version' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'file_id.required' => 'File ID cannot be empty',
            'file_id.integer' => 'File ID must be an integer',
            'file_id.min' => 'File ID must be greater than 0',
            'version.required' => 'Version cannot be empty',
            'version.integer' => 'Version must be an integer',
            'version.min' => 'Version must be greater than 0',
        ];
    }
}
