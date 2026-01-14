<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\TaskFileVersionEntity;

/**
 * File replaced event.
 */
class FileReplacedEvent
{
    public function __construct(
        private readonly TaskFileEntity $fileEntity,
        private readonly ?TaskFileVersionEntity $versionEntity,
        private readonly DelightfulUserAuthorization $userAuthorization,
        private readonly bool $isCrossTypeReplace
    ) {
    }

    /**
     * Get file entity.
     */
    public function getFileEntity(): TaskFileEntity
    {
        return $this->fileEntity;
    }

    /**
     * Get version entity.
     */
    public function getVersionEntity(): ?TaskFileVersionEntity
    {
        return $this->versionEntity;
    }

    /**
     * Get user authorization.
     */
    public function getUserAuthorization(): DelightfulUserAuthorization
    {
        return $this->userAuthorization;
    }

    /**
     * Check if this is a cross-type replace.
     */
    public function isCrossTypeReplace(): bool
    {
        return $this->isCrossTypeReplace;
    }

    /**
     * Get file ID.
     */
    public function getFileId(): int
    {
        return $this->fileEntity->getFileId();
    }

    /**
     * Get project ID.
     */
    public function getProjectId(): int
    {
        return $this->fileEntity->getProjectId();
    }

    /**
     * Get file name.
     */
    public function getFileName(): string
    {
        return $this->fileEntity->getFileName();
    }
}
