<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

/**
 * Get file version list request DTO.
 */
class GetFileVersionsRequestDTO extends AbstractRequestDTO
{
    /**
     * Page number (starting from 1).
     */
    public int $page = 1;

    /**
     * Items per page.
     */
    public int $pageSize = 10;

    /**
     * File ID.
     */
    protected int $fileId = 0;

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function setFileId(int|string $value): void
    {
        $this->fileId = (int) $value;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int|string $value): void
    {
        $this->page = (int) $value;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int|string $value): void
    {
        $this->pageSize = (int) $value;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'id' => 'required|integer|min:1',
            'page' => 'integer|min:1',
            'page_size' => 'integer|min:1|max:100',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'id.required' => 'File ID cannot be empty',
            'id.integer' => 'File ID must be an integer',
            'id.min' => 'File ID must be greater than 0',
            'page.integer' => 'Page must be an integer',
            'page.min' => 'Page must be greater than 0',
            'page_size.integer' => 'Page size must be an integer',
            'page_size.min' => 'Page size must be greater than 0',
            'page_size.max' => 'Page size cannot exceed 100',
        ];
    }
}
