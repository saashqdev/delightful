<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;

/**
 * Project operation log entity.
 */
class ProjectOperationLogEntity extends AbstractEntity
{
    /**
     * @var int Primary key ID
     */
    protected int $id = 0;

    /**
     * @var int Project ID
     */
    protected int $projectId = 0;

    /**
     * @var string User ID
     */
    protected string $userId = '';

    /**
     * @var string Organization code
     */
    protected string $organizationCode = '';

    /**
     * @var string Operation action
     */
    protected string $operationAction = '';

    /**
     * @var string Resource type
     */
    protected string $resourceType = '';

    /**
     * @var string Resource ID
     */
    protected string $resourceId = '';

    /**
     * @var string Resource name
     */
    protected string $resourceName = '';

    /**
     * @var array Operation details
     */
    protected array $operationDetails = [];

    /**
     * @var string Operation status
     */
    protected string $operationStatus = 'success';

    /**
     * @var null|string IP address
     */
    protected ?string $ipAddress = null;

    /**
     * @var string Creation time
     */
    protected string $createdAt = '';

    /**
     * @var string Update time
     */
    protected string $updatedAt = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    public function getOperationAction(): string
    {
        return $this->operationAction;
    }

    public function setOperationAction(string $operationAction): self
    {
        $this->operationAction = $operationAction;
        return $this;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function setResourceType(string $resourceType): self
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;
        return $this;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): self
    {
        $this->resourceName = $resourceName;
        return $this;
    }

    public function getOperationDetails(): array
    {
        return $this->operationDetails;
    }

    public function setOperationDetails(array $operationDetails): self
    {
        $this->operationDetails = $operationDetails;
        return $this;
    }

    public function getOperationStatus(): string
    {
        return $this->operationStatus;
    }

    public function setOperationStatus(string $operationStatus): self
    {
        $this->operationStatus = $operationStatus;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
