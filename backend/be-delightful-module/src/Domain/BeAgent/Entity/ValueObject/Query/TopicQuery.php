<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\Query;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;

/**
 * Topic query value object that encapsulates query conditions.
 */
class TopicQuery
{
    /**
     * @var null|string Topic ID
     */
    private ?string $topicId = null;

    /**
     * @var null|string Topic name
     */
    private ?string $topicName = null;

    /**
     * @var null|string Topic status
     */
    private ?string $topicStatus = null;

    /**
     * @var null|string Sandbox ID
     */
    private ?string $sandboxId = null;

    /**
     * @var null|string Organization code
     */
    private ?string $organizationCode = null;

    /**
     * @var null|array User ID list, used to filter by user ID
     */
    private ?array $userIds = null;

    /**
     * @var null|string Project ID
     */
    private ?string $projectId = null;

    /**
     * @var int Page number
     */
    private int $page = 1;

    /**
     * @var int Results per page
     */
    private int $pageSize = 20;

    /**
     * @var string Sort field
     */
    private string $orderBy = 'id';

    private string $order = 'desc';

    /**
     * Get Topic ID.
     */
    public function getTopicId(): ?string
    {
        return $this->topicId;
    }

    /**
     * Set Topic ID.
     */
    public function setTopicId(?string $topicId): self
    {
        $this->topicId = $topicId;
        return $this;
    }

    /**
     * Get Topic name.
     */
    public function getTopicName(): ?string
    {
        return $this->topicName;
    }

    /**
     * Set Topic name.
     */
    public function setTopicName(?string $topicName): self
    {
        $this->topicName = $topicName;
        return $this;
    }

    /**
     * Get Topic status.
     */
    public function getTopicStatus(): ?string
    {
        return $this->topicStatus;
    }

    /**
     * Set Topic status.
     */
    public function setTopicStatus(?string $topicStatus): self
    {
        $this->topicStatus = $topicStatus;
        return $this;
    }

    /**
     * Get Sandbox ID.
     */
    public function getSandboxId(): ?string
    {
        return $this->sandboxId;
    }

    /**
     * Set Sandbox ID.
     */
    public function setSandboxId(?string $sandboxId): self
    {
        $this->sandboxId = $sandboxId;
        return $this;
    }

    /**
     * Get Organization code
     */
    public function getOrganizationCode(): ?string
    {
        return $this->organizationCode;
    }

    /**
     * Set Organization code
     */
    public function setOrganizationCode(?string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    /**
     * Get User ID list.
     */
    public function getUserIds(): ?array
    {
        return $this->userIds;
    }

    /**
     * Set User ID list.
     */
    public function setUserIds(?array $userIds): self
    {
        $this->userIds = $userIds;
        return $this;
    }

    /**
     * Get Project ID.
     */
    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    /**
     * Set Project ID.
     */
    public function setProjectId(?string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * Get page number
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set page number
     */
    public function setPage(int $page): self
    {
        $this->page = max(1, $page);
        return $this;
    }

    /**
     * Get results per page.
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Set results per page.
     */
    public function setPageSize(int $pageSize): self
    {
        $this->pageSize = max(1, $pageSize);
        return $this;
    }

    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrder(string $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * Convert to conditions array.
     */
    public function toConditions(): array
    {
        $conditions = [];

        if ($this->topicId !== null) {
            $conditions['id'] = (int) $this->topicId;
        }

        if ($this->topicName !== null) {
            $conditions['topic_name'] = $this->topicName;
        }

        if ($this->topicStatus !== null) {
            $conditions['current_task_status'] = $this->topicStatus;
        } else {
            $conditions['current_task_status'] = [TaskStatus::RUNNING, TaskStatus::FINISHED, TaskStatus::ERROR, TaskStatus::Suspended, TaskStatus::Stopped];
        }

        if ($this->sandboxId !== null) {
            $conditions['sandbox_id'] = $this->sandboxId;
        }

        if ($this->organizationCode !== null) {
            $conditions['user_organization_code'] = $this->organizationCode;
        }

        if ($this->userIds !== null && ! empty($this->userIds)) {
            $conditions['user_id'] = $this->userIds;
        }

        if ($this->projectId !== null) {
            $conditions['project_id'] = (int) $this->projectId;
        }

        return $conditions;
    }
}
