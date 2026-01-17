<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * User information value object.
 */
class UserInfoValueObject
{
    /**
     * Constructor.
     *
     * @param string $id User ID
     * @param string $nickname User nickname
     * @param string $realName Real name
     * @param string $workNumber Work number
     * @param string $position Position
     * @param array $departments Department information array
     */
    public function __construct(
        private string $id = '',
        private string $nickname = '',
        private string $realName = '',
        private string $workNumber = '',
        private string $position = '',
        private array $departments = []
    ) {
        // Ensure departments are DepartmentInfoValueObject instances
        $this->departments = array_map(function ($dept) {
            return $dept instanceof DepartmentInfoValueObject
                ? $dept
                : DepartmentInfoValueObject::fromArray($dept);
        }, $this->departments);
    }

    /**
     * Create user information object from array.
     *
     * @param array $data User information array
     */
    public static function fromArray(array $data): self
    {
        $departments = [];
        if (isset($data['departments']) && is_array($data['departments'])) {
            $departments = array_map(function ($dept) {
                return is_array($dept)
                    ? DepartmentInfoValueObject::fromArray($dept)
                    : $dept;
            }, $data['departments']);
        }

        return new self(
            $data['id'] ?? '',
            $data['nickname'] ?? '',
            $data['real_name'] ?? '',
            $data['work_number'] ?? '',
            $data['position'] ?? '',
            $departments
        );
    }

    /**
     * Convert to array.
     *
     * @return array User information array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'real_name' => $this->realName,
            'work_number' => $this->workNumber,
            'position' => $this->position,
            'departments' => array_map(fn ($dept) => $dept->toArray(), $this->departments),
        ];
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function getWorkNumber(): string
    {
        return $this->workNumber;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * @return DepartmentInfoValueObject[]
     */
    public function getDepartments(): array
    {
        return $this->departments;
    }

    // Withers for immutability
    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withNickname(string $nickname): self
    {
        $clone = clone $this;
        $clone->nickname = $nickname;
        return $clone;
    }

    public function withRealName(string $realName): self
    {
        $clone = clone $this;
        $clone->realName = $realName;
        return $clone;
    }

    public function withWorkNumber(string $workNumber): self
    {
        $clone = clone $this;
        $clone->workNumber = $workNumber;
        return $clone;
    }

    public function withPosition(string $position): self
    {
        $clone = clone $this;
        $clone->position = $position;
        return $clone;
    }

    public function withDepartments(array $departments): self
    {
        $clone = clone $this;
        $clone->departments = array_map(function ($dept) {
            return $dept instanceof DepartmentInfoValueObject
                ? $dept
                : DepartmentInfoValueObject::fromArray($dept);
        }, $departments);
        return $clone;
    }

    /**
     * Check if user information is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->id) && empty($this->nickname) && empty($this->realName);
    }

    /**
     * Check if user information is valid.
     */
    public function isValid(): bool
    {
        return ! empty($this->id);
    }

    /**
     * Get primary department (first department).
     */
    public function getPrimaryDepartment(): ?DepartmentInfoValueObject
    {
        return $this->departments[0] ?? null;
    }

    /**
     * Check if user belongs to specified department.
     */
    public function belongsToDepartment(string $departmentId): bool
    {
        foreach ($this->departments as $department) {
            if ($department->getId() === $departmentId) {
                return true;
            }
        }
        return false;
    }
}
