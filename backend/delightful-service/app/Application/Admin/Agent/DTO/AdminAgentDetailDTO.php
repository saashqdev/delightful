<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\DTO;

use App\Domain\Agent\Entity\ValueObject\Visibility\VisibilityConfig;
use App\Infrastructure\Core\AbstractDTO;
use App\Interfaces\Permission\DTO\ResourceAccessDTO;

class AdminAgentDetailDTO extends AbstractDTO
{
    // primary key
    protected string $id;

    // assistant name
    protected string $agentName;

    // 助理description
    protected string $agentDescription;

    // assistant creator
    protected string $createdUid;

    // assistant avatar
    protected string $agentAvatar;

    // assistant creator nickname
    protected string $createdName;

    // version number，ifnothaveversion numberthenfor：no version
    protected string $versionNumber;

    // enabled status
    protected int $status;

    // creation time
    protected string $createdAt;

    // permission control
    protected ResourceAccessDTO $resourceAccess;

    // assistant visibility
    protected ?VisibilityConfig $visibilityConfig;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getAgentName(): string
    {
        return $this->agentName;
    }

    public function setAgentName(string $agentName): void
    {
        $this->agentName = $agentName;
    }

    public function getAgentDescription(): string
    {
        return $this->agentDescription;
    }

    public function setAgentDescription(string $agentDescription): void
    {
        $this->agentDescription = $agentDescription;
    }

    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(string $createdUid): void
    {
        $this->createdUid = $createdUid;
    }

    public function getCreatedName(): string
    {
        return $this->createdName;
    }

    public function setCreatedName(string $createdName): void
    {
        $this->createdName = $createdName;
    }

    public function getVersionNumber(): string
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(string $versionNumber): void
    {
        $this->versionNumber = $versionNumber;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getResourceAccess(): ResourceAccessDTO
    {
        return $this->resourceAccess;
    }

    public function setResourceAccess(ResourceAccessDTO $resourceAccess): void
    {
        $this->resourceAccess = $resourceAccess;
    }

    public function getVisibilityConfig(): ?VisibilityConfig
    {
        return $this->visibilityConfig;
    }

    public function setVisibilityConfig(?VisibilityConfig $visibilityConfig): void
    {
        $this->visibilityConfig = $visibilityConfig;
    }

    public function getAgentAvatar(): string
    {
        return $this->agentAvatar;
    }

    public function setAgentAvatar(string $agentAvatar): void
    {
        $this->agentAvatar = $agentAvatar;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
