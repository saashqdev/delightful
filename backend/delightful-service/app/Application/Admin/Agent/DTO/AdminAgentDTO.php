<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\DTO;

use App\Infrastructure\Core\AbstractDTO;

class AdminAgentDTO extends AbstractDTO
{
    // primary key
    protected string $id;

    // name
    protected string $agentName;

    // description
    protected string $agentDescription;

    // avatar
    protected string $agentAvatar;

    // status
    protected int $status;

    // createpersonid
    protected string $createdUid;

    // creation time
    protected string $createdAt;

    // createpersonname
    protected string $createdName;

    // publishstatus
    protected ?int $releaseScope = null;

    // reviewstatus
    protected ?int $reviewStatus = null;

    // approvalstatus
    protected ?int $approvalStatus = null;

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

    public function getAgentAvatar(): string
    {
        return $this->agentAvatar;
    }

    public function setAgentAvatar(string $agentAvatar): void
    {
        $this->agentAvatar = $agentAvatar;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(string $createdUid): void
    {
        $this->createdUid = $createdUid;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedName(): string
    {
        return $this->createdName;
    }

    public function setCreatedName(string $createdName): void
    {
        $this->createdName = $createdName;
    }

    public function getReleaseScope(): ?int
    {
        return $this->releaseScope;
    }

    public function setReleaseScope(?int $releaseScope): void
    {
        $this->releaseScope = $releaseScope;
    }

    public function getReviewStatus(): ?int
    {
        return $this->reviewStatus;
    }

    public function setReviewStatus(?int $reviewStatus): void
    {
        $this->reviewStatus = $reviewStatus;
    }

    public function getApprovalStatus(): ?int
    {
        return $this->approvalStatus;
    }

    public function setApprovalStatus(?int $approvalStatus): void
    {
        $this->approvalStatus = $approvalStatus;
    }
}
