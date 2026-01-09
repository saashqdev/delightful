<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Entity;

use App\Domain\Chat\Entity\AbstractEntity;

class CommentEntity extends AbstractEntity
{
    /**
     * primary keyid.
     */
    protected int $id;

    /**
     * type，for example评论、动态.
     */
    protected int $type;

    /**
     * 评论的资源id，for example云documentid、sheet表id.
     */
    protected int $resourceId;

    /**
     * 评论的资源type，for example云document、sheet表.
     */
    protected int $resourceType;

    /**
     * 父级评论的primary keyid.
     */
    protected int $parentId;

    /**
     * 对评论的简短description，主要是给动态用的，for examplecreate待办、uploadimage等system动态.
     */
    protected string $description = '';

    /**
     * 评论的content.
     */
    protected ?array $message = [];

    /**
     * @var Attachment[]
     */
    protected ?array $attachments = null;

    /**
     * create人.
     */
    protected string $creator;

    /**
     * organizationcode.
     */
    protected string $organizationCode;

    /**
     * creation time.
     */
    protected string $createdAt;

    /**
     * update time.
     */
    protected string $updatedAt;

    public function appendAttachment(Attachment $attachment): static
    {
        if ($this->attachments === null) {
            $this->attachments = [];
        }
        $this->attachments[] = $attachment;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    public function setResourceId(int $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceType(): int
    {
        return $this->resourceType;
    }

    public function setResourceType(int $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getMessage(): ?array
    {
        return $this->message;
    }

    public function setMessage(?array $message): void
    {
        $this->message = $message;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
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
}
