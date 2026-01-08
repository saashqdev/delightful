<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity;

use App\Domain\Chat\Entity\AbstractEntity;

class DelightfulOrganizationEnvEntity extends AbstractEntity
{
    protected ?string $id = null;

    protected string $loginCode;

    protected string $delightfulOrganizationCode;

    protected string $originOrganizationCode;

    protected int $environmentId;

    protected string $createdAt;

    protected string $updatedAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getLoginCode(): string
    {
        return $this->loginCode;
    }

    public function setLoginCode(string $loginCode): void
    {
        $this->loginCode = $loginCode;
    }

    public function getDelightfulOrganizationCode(): string
    {
        return $this->delightfulOrganizationCode;
    }

    public function setDelightfulOrganizationCode(string $delightfulOrganizationCode): void
    {
        $this->delightfulOrganizationCode = $delightfulOrganizationCode;
    }

    public function getOriginOrganizationCode(): string
    {
        return $this->originOrganizationCode;
    }

    public function setOriginOrganizationCode(string $originOrganizationCode): void
    {
        $this->originOrganizationCode = $originOrganizationCode;
    }

    public function getEnvironmentId(): int
    {
        return $this->environmentId;
    }

    public function setEnvironmentId(int $environmentId): void
    {
        $this->environmentId = $environmentId;
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
