<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\DTO;

use App\Domain\Contact\Entity\AbstractEntity;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\UserQueryType;

class UserQueryDTO extends AbstractEntity
{
    protected string $query = '';

    /**
     * @var string[]
     */
    protected array $userIds = [];

    protected string $departmentId = '';

    /**
     * 上一页的token. 对atmysql来说,return累积偏移量;对ates来说,return游标.
     */
    protected string $pageToken = '';

    // is_recursive whether递归query
    protected bool $isRecursive = false;

    /**
     * 1: 人员
     * 2: 人员 + department.
     */
    protected UserQueryType $queryType = UserQueryType::User;

    protected bool $filterAgent = false;

    protected bool $queryByDepartmentPath = false;

    /**
     * @var null|DelightfulDepartmentEntity[]
     */
    protected ?array $matchedQueryDepartmentIds = null;

    protected bool $queryByJobTitle = false;

    protected int $pageSize = 50;

    public function isFilterAgent(): bool
    {
        return $this->filterAgent;
    }

    public function setFilterAgent(bool $filterAgent): UserQueryDTO
    {
        $this->filterAgent = $filterAgent;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    public function getQueryType(): UserQueryType
    {
        return $this->queryType;
    }

    public function setQueryType(UserQueryType $queryType): void
    {
        $this->queryType = $queryType;
    }

    public function getPageToken(): string
    {
        return $this->pageToken;
    }

    public function setPageToken(string $pageToken): void
    {
        $this->pageToken = $pageToken;
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    public function setDepartmentId(string $departmentId): void
    {
        $this->departmentId = $departmentId;
    }

    public function isRecursive(): bool
    {
        return $this->isRecursive;
    }

    public function setIsRecursive(bool $isRecursive): void
    {
        $this->isRecursive = $isRecursive;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): UserQueryDTO
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    public function isQueryByDepartmentPath(): bool
    {
        return $this->queryByDepartmentPath;
    }

    public function setQueryByDepartmentPath(bool $queryByDepartmentPath): UserQueryDTO
    {
        $this->queryByDepartmentPath = $queryByDepartmentPath;
        return $this;
    }

    public function isQueryByJobTitle(): bool
    {
        return $this->queryByJobTitle;
    }

    public function setQueryByJobTitle(bool $queryByJobTitle): UserQueryDTO
    {
        $this->queryByJobTitle = $queryByJobTitle;
        return $this;
    }

    public function getMatchedQueryDepartmentIds(): ?array
    {
        return $this->matchedQueryDepartmentIds;
    }

    public function setMatchedQueryDepartmentIds(?array $matchedQueryDepartmentIds): UserQueryDTO
    {
        $this->matchedQueryDepartmentIds = $matchedQueryDepartmentIds;
        return $this;
    }
}
