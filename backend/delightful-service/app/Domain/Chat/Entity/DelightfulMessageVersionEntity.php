<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity;

class DelightfulMessageVersionEntity extends AbstractEntity
{
    protected string $versionId;

    protected string $delightfulMessageId;

    protected string $messageContent;

    protected ?string $messageType;

    protected string $createdAt;

    protected string $updatedAt;

    protected ?string $deletedAt;

    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    public function getVersionId(): string
    {
        return $this->versionId;
    }

    public function setVersionId(string $versionId): static
    {
        $this->versionId = $versionId;
        return $this;
    }

    public function getDelightfulMessageId(): string
    {
        return $this->delightfulMessageId;
    }

    public function setDelightfulMessageId(string $delightfulMessageId): static
    {
        $this->delightfulMessageId = $delightfulMessageId;
        return $this;
    }

    public function getMessageContent(): string
    {
        return $this->messageContent;
    }

    public function setMessageContent(string $messageContent): static
    {
        $this->messageContent = $messageContent;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): static
    {
        $this->messageType = $messageType;
        return $this;
    }
}
