<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\DTO;

use App\Infrastructure\Core\AbstractDTO;

class ShareableResourceDTO extends AbstractDTO
{
    /**
     * Resource ID.
     */
    public string $resourceId = '';

    /**
     * Resource type.
     */
    public string $resourceType = '';

    /**
     * Resource name.
     */
    public string $resourceName = '';

    /**
     * Resource owner ID.
     */
    public string $ownerId = '';

    /**
     * Resource description.
     */
    public string $description = '';

    /**
     * Creation time.
     */
    public string $createdAt = '';

    /**
     * Update time.
     */
    public string $updatedAt = '';

    /**
     * Additional attributes.
     */
    public array $extraAttributes = [];

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function setResourceType(string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getExtraAttributes(): array
    {
        return $this->extraAttributes;
    }

    public function setExtraAttributes(array $extraAttributes): void
    {
        $this->extraAttributes = $extraAttributes;
    }
}
