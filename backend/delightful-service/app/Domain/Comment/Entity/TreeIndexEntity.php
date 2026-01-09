<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Entity;

class TreeIndexEntity
{
    protected int $id;

    /**
     * ancestorsectionpointid.
     */
    protected int $ancestorId;

    /**
     * backgenerationsectionpointid.
     */
    protected int $descendantId;

    /**
     * ancestorsectionpointtobackgenerationsectionpointdistance.
     */
    protected int $distance;

    /**
     * organizationcode.
     */
    protected string $organizationCode;

    /**
     * createtime.
     */
    protected string $createdAt;

    /**
     * updatetime.
     */
    protected string $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAncestorId(): int
    {
        return $this->ancestorId;
    }

    public function setAncestorId(int $ancestorId): void
    {
        $this->ancestorId = $ancestorId;
    }

    public function getDescendantId(): int
    {
        return $this->descendantId;
    }

    public function setDescendantId(int $descendantId): void
    {
        $this->descendantId = $descendantId;
    }

    public function getDistance(): int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): void
    {
        $this->distance = $distance;
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
