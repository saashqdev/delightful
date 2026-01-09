<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Entity;

use App\Domain\Agent\Entity\ValueObject\Visibility\VisibilityConfig;
use Hyperf\Codec\Json;

class DelightfulAgentVersionEntity extends AbstractEntity
{
    /**
     * primary key.
     */
    protected string $id = '';

    /**
     * workflow id.
     */
    protected string $flowCode;

    /**
     * workflowversionnumber.
     */
    protected string $flowVersion;

    // 交互finger令
    protected ?array $instructs = [];

    /**
     * 助理id.
     */
    protected string $agentId = '';

    protected string $rootId = '';

    /**
     * 助理name.
     */
    protected string $agentName;

    protected string $robotName;

    /**
     * 助理avatar.
     */
    protected string $agentAvatar = '';

    protected string $robotAvatar;

    /**
     * 助理description.
     */
    protected string $agentDescription;

    protected string $robotDescription;

    /**
     * versiondescription.
     */
    protected ?string $versionDescription = '';

    /**
     * versionnumber.
     */
    protected ?string $versionNumber = '';

    /**
     * publishrange. 0:personuse,1:企业inside部,2:application市场.
     */
    protected ?int $releaseScope = 0;

    /**
     * approvalstatus.
     */
    protected ?int $approvalStatus;

    /**
     * reviewstatus.
     */
    protected ?int $reviewStatus;

    /**
     * publishto企业inside部status.
     */
    protected ?int $enterpriseReleaseStatus;

    /**
     * publishtoapplication市场status.
     */
    protected ?int $appMarketStatus;

    /**
     * publishperson.
     */
    protected string $createdUid = '';

    /**
     * organizationencoding
     */
    protected string $organizationCode;

    /**
     * createtime.
     */
    protected ?string $createdAt = null;

    /**
     * update者userID.
     */
    protected ?string $updatedUid = '';

    /**
     * updatetime.
     */
    protected ?string $updatedAt = null;

    /**
     * deletetime.
     */
    protected ?string $deletedAt = null;

    protected bool $startPage = false;

    /**
     * visiblepropertyconfiguration.
     */
    protected ?VisibilityConfig $visibilityConfig = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(int|string $id): void
    {
        $this->id = (string) $id;
    }

    public function getFlowCode(): string
    {
        return $this->flowCode;
    }

    public function setFlowCode(string $flowCode): void
    {
        $this->flowCode = $flowCode;
    }

    public function getAgentId(): string
    {
        return $this->agentId;
    }

    public function setAgentId(int|string $agentId): void
    {
        $this->agentId = (string) $agentId;
        $this->rootId = $this->agentId;
    }

    public function getAgentName(): string
    {
        return $this->agentName;
    }

    public function setAgentName(string $agentName): void
    {
        $this->agentName = $agentName;
        $this->robotName = $agentName;
    }

    public function getAgentAvatar(): string
    {
        return $this->agentAvatar;
    }

    public function setAgentAvatar(string $agentAvatar): void
    {
        $this->agentAvatar = $agentAvatar;
        $this->robotAvatar = $agentAvatar;
    }

    public function getAgentDescription(): string
    {
        return $this->agentDescription;
    }

    public function setAgentDescription(string $agentDescription): void
    {
        $this->agentDescription = $agentDescription;
        $this->robotDescription = $agentDescription;
    }

    public function getVersionDescription(): ?string
    {
        return $this->versionDescription;
    }

    public function setVersionDescription(?string $versionDescription): void
    {
        $this->versionDescription = $versionDescription;
    }

    public function getVersionNumber(): ?string
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(?string $versionNumber): void
    {
        $this->versionNumber = $versionNumber;
    }

    public function getReleaseScope(): ?int
    {
        return $this->releaseScope;
    }

    public function setReleaseScope(?int $releaseScope): void
    {
        $this->releaseScope = $releaseScope;
    }

    public function getApprovalStatus(): ?int
    {
        return $this->approvalStatus;
    }

    public function setApprovalStatus(?int $approvalStatus): void
    {
        $this->approvalStatus = $approvalStatus;
    }

    public function getReviewStatus(): ?int
    {
        return $this->reviewStatus;
    }

    public function setReviewStatus(?int $reviewStatus): void
    {
        $this->reviewStatus = $reviewStatus;
    }

    public function getEnterpriseReleaseStatus(): ?int
    {
        return $this->enterpriseReleaseStatus;
    }

    public function setEnterpriseReleaseStatus(?int $enterpriseReleaseStatus): void
    {
        $this->enterpriseReleaseStatus = $enterpriseReleaseStatus;
    }

    public function getAppMarketStatus(): ?int
    {
        return $this->appMarketStatus;
    }

    public function setAppMarketStatus(?int $appMarketStatus): void
    {
        $this->appMarketStatus = $appMarketStatus;
    }

    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(string $createdUid): void
    {
        $this->createdUid = $createdUid;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedUid(): ?string
    {
        return $this->updatedUid;
    }

    public function setUpdatedUid(?string $updatedUid): void
    {
        $this->updatedUid = $updatedUid;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getFlowVersion(): string
    {
        return $this->flowVersion;
    }

    public function setFlowVersion(string $flowVersion): void
    {
        $this->flowVersion = $flowVersion;
    }

    public function getInstructs(): ?array
    {
        return $this->instructs;
    }

    public function setInstructs(null|array|string $instructs): void
    {
        if (is_string($instructs)) {
            $this->instructs = Json::decode($instructs);
        } elseif (is_array($instructs)) {
            $this->instructs = $instructs;
        }
    }

    public function setStartPage(bool|int $startPage): void
    {
        $this->startPage = (bool) $startPage;
    }

    public function getStartPage(): bool
    {
        return $this->startPage;
    }

    public function getVisibilityConfig(): ?VisibilityConfig
    {
        return $this->visibilityConfig;
    }

    public function setVisibilityConfig(null|array|string|VisibilityConfig $visibilityConfig): void
    {
        if (is_array($visibilityConfig)) {
            $visibilityConfig = new VisibilityConfig($visibilityConfig);
        }
        if (is_string($visibilityConfig)) {
            $visibilityConfig = new VisibilityConfig(Json::decode($visibilityConfig));
        }
        $this->visibilityConfig = $visibilityConfig;
    }

    public function setRootId(int|string $agentId): void
    {
        $this->rootId = (string) $agentId;
        $this->agentId = (string) $agentId;
    }

    public function setRobotName(string $agentName): void
    {
        $this->robotName = $agentName;
        $this->agentName = $agentName;
    }

    public function setRobotAvatar(string $agentAvatar): void
    {
        $this->robotAvatar = $agentAvatar;
        $this->agentAvatar = $agentAvatar;
    }

    public function setRobotDescription(string $agentDescription): void
    {
        $this->robotDescription = $agentDescription;
        $this->agentDescription = $agentDescription;
    }

    public function getRootId(): string
    {
        return $this->rootId;
    }

    public function getRobotName(): string
    {
        return $this->robotName;
    }

    public function getRobotAvatar(): string
    {
        return $this->robotAvatar;
    }

    public function getRobotDescription(): string
    {
        return $this->robotDescription;
    }
}
