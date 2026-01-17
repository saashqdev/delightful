<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/**
 * Sandbox file save request class
 * Used to call the sandbox /api/v1/files/edit endpoint.
 */
class SaveFilesRequest
{
    private array $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * Create a file save request
     */
    public static function create(array $files): self
    {
        return new self($files);
    }

    /**
     * Create request from application-level file data
     */
    public static function fromFileData(array $fileDataList): self
    {
        $files = [];

        foreach ($fileDataList as $fileData) {
            $files[] = [
                'file_key' => $fileData['file_key'],
                'file_path' => $fileData['file_path'],
                'content' => $fileData['content'],
                'is_encrypted' => false,
            ];
        }

        return new self($files);
    }

    /**
     * Convert to array format (for API call)
     */
    public function toArray(): array
    {
        return [
            'files' => $this->files,
        ];
    }

    /**
     * Get file list.
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get file count.
     */
    public function getFileCount(): int
    {
        return count($this->files);
    }
}
