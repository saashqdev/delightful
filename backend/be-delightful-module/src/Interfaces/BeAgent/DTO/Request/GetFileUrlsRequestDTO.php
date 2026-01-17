<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\HttpServer\Contract\RequestInterface;

class GetFileUrlsRequestDTO
{
    /**
     * List of file IDs.
     */
    private array $fileIds;

    private string $token;

    private string $downloadMode;

    private string $topicId;

    private string $projectId;

    /**
     * Cache setting, default is true.
     */
    private bool $cache;

    /**
     * File version number mapping, format: [file_id => version_number]
     * If a file does not specify a version number, use the current version.
     */
    private array $fileVersions;

    /**
     * Constructor.
     */
    public function __construct(array $params)
    {
        $this->fileIds = $params['file_ids'] ?? [];
        $this->token = $params['token'] ?? '';
        $this->downloadMode = $params['download_mode'] ?? 'preview';
        $this->topicId = $params['topic_id'] ?? '';
        $this->projectId = $params['project_id'] ?? '';
        $this->cache = $params['cache'] ?? true;
        $this->fileVersions = $params['file_versions'] ?? [];

        $this->validate();
    }

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        return new self($request->all());
    }

    /**
     * Get file ID list.
     */
    public function getFileIds(): array
    {
        return $this->fileIds;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getDownloadMode(): string
    {
        return $this->downloadMode;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getCache(): bool
    {
        return $this->cache;
    }

    public function setProjectId(string $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Get file version number mapping.
     */
    public function getFileVersions(): array
    {
        return $this->fileVersions;
    }

    /**
     * Set file version number mapping.
     */
    public function setFileVersions(array $fileVersions): void
    {
        $this->fileVersions = $fileVersions;
    }

    /**
     * Get the version number of the specified file.
     *
     * @param int $fileId File ID
     * @return null|int Version number, returns null if not specified
     */
    public function getFileVersion(int $fileId): ?int
    {
        return $this->fileVersions[$fileId] ?? null;
    }

    /**
     * Validate request data.
     *
     * @throws BusinessException Throws exception if validation fails
     */
    /* @phpstan-ignore-next-line */
    private function validate(): void
    {
        if (empty($this->fileIds)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'file_ids.required');
        }

        if (empty($this->projectId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'project_id.required');
        }

        // Validate file version number format
        if (! empty($this->fileVersions)) {
            foreach ($this->fileVersions as $fileId => $version) {
                if (! is_numeric($fileId) || ! is_numeric($version) || (int) $version < 1) {
                    ExceptionBuilder::throw(
                        GenericErrorCode::ParameterValidationFailed,
                        'file_versions.invalid_format'
                    );
                }
            }
        }
    }
}
