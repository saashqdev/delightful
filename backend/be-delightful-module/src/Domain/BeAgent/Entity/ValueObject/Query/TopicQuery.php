<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\query ;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
/** * topic query ValueObjectquery Condition. */

class Topicquery 
{
 /** * @var null|string topic ID */ private ?string $topicId = null; /** * @var null|string topic Name */ private ?string $topicName = null; /** * @var null|string topic Status */ private ?string $topicStatus = null; /** * @var null|string ID */ private ?string $sandboxId = null; /** * @var null|string OrganizationCode */ private ?string $organizationCode = null; /** * @var null|array user IDlist for user IDFilter */ private ?array $userIds = null; /** * @var null|string Project ID */ private ?string $projectId = null; /** * @var int Page number */ 
    private int $page = 1; /** * @var int Per page */ 
    private int $pageSize = 20; /** * @var string SortField */ 
    private string $orderBy = 'id'; 
    private string $order = 'desc'; /** * Gettopic ID. */ 
    public function getTopicId(): ?string 
{
 return $this->topicId; 
}
 /** * Set topic ID. */ 
    public function setTopicId(?string $topicId): self 
{
 $this->topicId = $topicId; return $this; 
}
 /** * Gettopic Name. */ 
    public function getTopicName(): ?string 
{
 return $this->topicName; 
}
 /** * Set topic Name. */ 
    public function setTopicName(?string $topicName): self 
{
 $this->topicName = $topicName; return $this; 
}
 /** * Gettopic Status. */ 
    public function getTopicStatus(): ?string 
{
 return $this->topicStatus; 
}
 /** * Set topic Status. */ 
    public function setTopicStatus(?string $topicStatus): self 
{
 $this->topicStatus = $topicStatus; return $this; 
}
 /** * GetID. */ 
    public function getSandboxId(): ?string 
{
 return $this->sandboxId; 
}
 /** * Set ID. */ 
    public function setSandboxId(?string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 /** * GetOrganizationCode */ 
    public function getOrganizationCode(): ?string 
{
 return $this->organizationCode; 
}
 /** * Set OrganizationCode */ 
    public function setOrganizationCode(?string $organizationCode): self 
{
 $this->organizationCode = $organizationCode; return $this; 
}
 /** * Getuser IDlist . */ 
    public function getuser Ids(): ?array 
{
 return $this->userIds; 
}
 /** * Set user IDlist . */ 
    public function setuser Ids(?array $userIds): self 
{
 $this->userIds = $userIds; return $this; 
}
 /** * GetProject ID. */ 
    public function getProjectId(): ?string 
{
 return $this->projectId; 
}
 /** * Set Project ID. */ 
    public function setProjectId(?string $projectId): self 
{
 $this->projectId = $projectId; return $this; 
}
 /** * GetPage number */ 
    public function getPage(): int 
{
 return $this->page; 
}
 /** * Set Page number */ 
    public function setPage(int $page): self 
{
 $this->page = max(1, $page); return $this; 
}
 /** * GetPer page. */ 
    public function getPageSize(): int 
{
 return $this->pageSize; 
}
 /** * Set Per page. */ 
    public function setPageSize(int $pageSize): self 
{
 $this->pageSize = max(1, $pageSize); return $this; 
}
 
    public function setOrderBy(string $orderBy): self 
{
 $this->orderBy = $orderBy; return $this; 
}
 
    public function getOrderBy(): string 
{
 return $this->orderBy; 
}
 
    public function setOrder(string $order): self 
{
 $this->order = $order; return $this; 
}
 
    public function getOrder(): string 
{
 return $this->order; 
}
 /** * Convert toConditionArray. */ 
    public function toConditions(): array 
{
 $conditions = []; if ($this->topicId !== null) 
{
 $conditions['id'] = (int) $this->topicId; 
}
 if ($this->topicName !== null) 
{
 $conditions['topic_name'] = $this->topicName; 
}
 if ($this->topicStatus !== null) 
{
 $conditions['current_task_status'] = $this->topicStatus; 
}
 else 
{
 $conditions['current_task_status'] = [TaskStatus::RUNNING, TaskStatus::FINISHED, TaskStatus::ERROR, TaskStatus::Suspended, TaskStatus::Stopped]; 
}
 if ($this->sandboxId !== null) 
{
 $conditions['sandbox_id'] = $this->sandboxId; 
}
 if ($this->organizationCode !== null) 
{
 $conditions['user_organization_code'] = $this->organizationCode; 
}
 if ($this->userIds !== null && ! empty($this->userIds)) 
{
 $conditions['user_id'] = $this->userIds; 
}
 if ($this->projectId !== null) 
{
 $conditions['project_id'] = (int) $this->projectId; 
}
 return $conditions; 
}
 
}
 
