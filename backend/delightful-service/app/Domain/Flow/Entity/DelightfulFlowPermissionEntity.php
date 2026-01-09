<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity;

use App\Domain\Flow\Entity\ValueObject\Permission\Operation;
use App\Domain\Flow\Entity\ValueObject\Permission\ResourceType;
use App\Domain\Flow\Entity\ValueObject\Permission\TargetType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;

class DelightfulFlowPermissionEntity extends AbstractEntity
{
    protected ?int $id = null;

    protected string $organizationCode;

    /**
     * resourcetype.
     */
    protected ResourceType $resourceType;

    /**
     * resource.
     * 例子: process code、user id.
     */
    protected string $resourceId;

    /**
     * goaltype.
     */
    protected TargetType $targetType;

    /**
     * goal.
     * 例子: processopen放平台applicationid、api_key.
     */
    protected string $targetId;

    protected Operation $operation;

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    public function prepareForSave(): void
    {
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.organization_code.empty');
        }
        if (empty($this->resourceType)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.empty', ['label' => 'resource_type']);
        }
        if (empty($this->resourceId)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.empty', ['label' => 'resource_id']);
        }
        if (empty($this->targetType)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.empty', ['label' => 'target_type']);
        }
        if (empty($this->targetId)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.empty', ['label' => 'target_id']);
        }
        if (empty($this->operation)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.empty', ['label' => 'operation']);
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.creator.empty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        if (empty($this->modifier)) {
            $this->modifier = $this->creator;
        }
        if (empty($this->updatedAt)) {
            $this->updatedAt = $this->createdAt;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getResourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function setResourceType(ResourceType $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getTargetType(): TargetType
    {
        return $this->targetType;
    }

    public function setTargetType(TargetType $targetType): void
    {
        $this->targetType = $targetType;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    public function setTargetId(string $targetId): void
    {
        $this->targetId = $targetId;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): void
    {
        $this->modifier = $modifier;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
