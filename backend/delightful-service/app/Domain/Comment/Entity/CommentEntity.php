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
     * type,for examplecomment,autostate.
     */
    protected int $type;

    /**
     * commentresourceid,for exampleclouddocumentid,sheettableid.
     */
    protected int $resourceId;

    /**
     * commentresourcetype,for exampleclouddocument,sheettable.
     */
    protected int $resourceType;

    /**
     * parentlevelcommentprimary keyid.
     */
    protected int $parentId;

    /**
     * tocommentsimpleshortdescription,mainisgiveautostateuse,for examplecreatetodo,uploadimageetcsystemautostate.
     */
    protected string $description = '';

    /**
     * commentcontent.
     */
    protected ?array $message = [];

    /**
     * @var Attachment[]
     */
    protected ?array $attachments = null;

    /**
     * createperson.
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
