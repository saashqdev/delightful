<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use JsonSerializable;

/**
 * Project file upload STS Token request DTO.
 */
class ProjectUploadTokenRequestDTO implements JsonSerializable
{
    /**
     * Project ID.
     */
    private string $projectId = '';

    /**
     * Credential validity period (seconds).
     */
    private int $expires = 3600;

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        $instance = new self();

        $instance->projectId = $data['project_id'] ?? '';
        $instance->expires = (int) ($data['expires'] ?? 3600);

        return $instance;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function setProjectId(string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function setExpires(int $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * Implement JsonSerializable interface.
     */
    public function jsonSerialize(): array
    {
        return [
            'project_id' => $this->projectId,
            'expires' => $this->expires,
        ];
    }
}
