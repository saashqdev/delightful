<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Infrastructure\Core\AbstractDTO;

/**
 * Collaborator member information DTO.
 */
class CollaboratorMemberDTO extends AbstractDTO
{
    /**
     * @var string Member ID (user_id or department_id)
     */
    protected string $id = '';

    /**
     * @var string Member name
     */
    protected string $name = '';

    /**
     * @var string Avatar URL
     */
    protected string $avatarUrl = '';

    /**
     * @var string Member type User|Department
     */
    protected string $type = '';

    /**
     * Create DTO from DelightfulUserEntity object.
     */
    public static function fromUserEntity(DelightfulUserEntity $userEntity): self
    {
        $dto = new self();
        $dto->setId($userEntity->getUserId());
        $dto->setName($userEntity->getNickname());
        $dto->setAvatarUrl($userEntity->getAvatarUrl());
        $dto->setType('User');

        return $dto;
    }

    /**
     * Create DTO from DelightfulDepartmentEntity object.
     */
    public static function fromDepartmentEntity(DelightfulDepartmentEntity $departmentEntity): self
    {
        $dto = new self();
        $dto->setId($departmentEntity->getDepartmentId() ?? '');
        $dto->setName($departmentEntity->getName() ?? '');
        $dto->setAvatarUrl(''); // Departments typically don't have avatars
        $dto->setType('Department');

        return $dto;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'avatar_url' => $this->avatarUrl,
            'type' => $this->type,
        ];

        // Add corresponding ID field based on type
        if ($this->type === 'User') {
            $result['user_id'] = $this->id;
        } elseif ($this->type === 'Department') {
            $result['department_id'] = $this->id;
        }

        return $result;
    }

    // Getters and Setters
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}
