<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response;

/**
 * Sandbox file save response class
 * Parses the sandbox /api/v1/files/edit response data.
 */
class SaveFilesResponse
{
    private array $editSummary;

    private array $results;

    public function __construct(array $editSummary, array $results)
    {
        $this->editSummary = $editSummary;
        $this->results = $results;
    }

    /**
     * Create a response object from API response data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            $data['edit_summary'] ?? [],
            $data['results'] ?? []
        );
    }

    /**
     * Get edit summary
     */
    public function getEditSummary(): array
    {
        return $this->editSummary;
    }

    /**
     * Get detailed result list.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Check whether all files succeeded
     */
    public function isAllSuccess(): bool
    {
        return $this->editSummary['all_success'] ?? false;
    }

    /**
     * Check whether all files were uploaded successfully
     */
    public function isAllUploaded(): bool
    {
        return $this->editSummary['all_uploaded'] ?? false;
    }

    /**
     * Get success count.
     */
    public function getSuccessCount(): int
    {
        return $this->editSummary['success_count'] ?? 0;
    }

    /**
     * Get failure count.
     */
    public function getFailedCount(): int
    {
        return $this->editSummary['failed_count'] ?? 0;
    }

    /**
     * Get total count.
     */
    public function getTotalCount(): int
    {
        return $this->editSummary['total_count'] ?? 0;
    }

    /**
     * Get upload success count.
     */
    public function getUploadSuccessCount(): int
    {
        return $this->editSummary['upload_success_count'] ?? 0;
    }

    /**
     * Convert to array format (compatible with the original API)
     */
    public function toArray(): array
    {
        return [
            'edit_summary' => $this->editSummary,
            'results' => $this->results,
        ];
    }

    /**
     * Get the list of failed files.
     */
    public function getFailedFiles(): array
    {
        return array_filter($this->results, function ($result) {
            return ! ($result['success'] ?? true);
        });
    }

    /**
     * Get the list of successful files.
     */
    public function getSuccessFiles(): array
    {
        return array_filter($this->results, function ($result) {
            return $result['success'] ?? true;
        });
    }
}
