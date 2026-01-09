<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity;

use App\Domain\Contact\Entity\ValueObject\EmployeeType;
use ArrayAccess;

/**
 * 之fronttoo多placeusearrayaccess，这withinuse ArrayAccess interfacecomeimplementarrayaccess.
 */
class DelightfulDepartmentUserEntity extends AbstractEntity implements ArrayAccess
{
    protected string $id = '';

    protected string $delightfulId = '';

    protected string $userId = '';

    protected string $departmentId = '';

    protected int $isLeader = 0;

    protected string $jobTitle = '';

    protected string $leaderUserId = '';

    protected string $organizationCode = '';

    protected string $city = '';

    protected string $country = '';

    protected string $joinTime = '';

    protected string $employeeNo = '';

    protected EmployeeType $employeeType = EmployeeType::Formal;

    protected string $orders = '';

    protected string $customAttrs = '';

    protected int $isFrozen = 0;

    protected string $createdAt = '';

    protected string $updatedAt = '';

    protected ?string $deletedAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(int|string $id): void
    {
        if (is_int($id)) {
            $id = (string) $id;
        }
        $this->id = $id;
    }

    public function getDelightfulId(): string
    {
        return $this->delightfulId;
    }

    public function setDelightfulId(int|string $delightfulId): void
    {
        if (is_int($delightfulId)) {
            $delightfulId = (string) $delightfulId;
        }
        $this->delightfulId = $delightfulId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    public function setDepartmentId(int|string $departmentId): void
    {
        if (is_int($departmentId)) {
            $departmentId = (string) $departmentId;
        }
        $this->departmentId = $departmentId;
    }

    public function getIsLeader(): int
    {
        return $this->isLeader;
    }

    public function setIsLeader(int $isLeader): void
    {
        $this->isLeader = $isLeader;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    public function getLeaderUserId(): string
    {
        return $this->leaderUserId;
    }

    public function setLeaderUserId(?string $leaderUserId): void
    {
        $this->leaderUserId = $leaderUserId ?? '';
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getJoinTime(): string
    {
        return $this->joinTime;
    }

    public function setJoinTime(string $joinTime): void
    {
        $this->joinTime = $joinTime;
    }

    public function getEmployeeNo(): string
    {
        return $this->employeeNo;
    }

    public function setEmployeeNo(string $employeeNo): void
    {
        $this->employeeNo = $employeeNo;
    }

    public function getEmployeeType(): EmployeeType
    {
        return $this->employeeType;
    }

    public function setEmployeeType(null|EmployeeType|int|string $employeeType): void
    {
        if (is_numeric($employeeType)) {
            $employeeType = EmployeeType::from((int) $employeeType);
        }
        if ($employeeType === null) {
            $employeeType = EmployeeType::Formal;
        }
        $this->employeeType = $employeeType;
    }

    public function getOrders(): string
    {
        return $this->orders;
    }

    public function setOrders(string $orders): void
    {
        $this->orders = $orders;
    }

    public function getCustomAttrs(): string
    {
        return $this->customAttrs;
    }

    public function setCustomAttrs(string $customAttrs): void
    {
        $this->customAttrs = $customAttrs;
    }

    public function getIsFrozen(): int
    {
        return $this->isFrozen;
    }

    public function setIsFrozen(int $isFrozen): void
    {
        $this->isFrozen = $isFrozen;
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

    public function getDeletedAt(): string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function isTopLevel(): bool
    {
        return $this->departmentId === '-1';
    }
}
