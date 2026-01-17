<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Collaboration project creator information DTO.
 */
class CollaborationCreatorItemDTO extends AbstractDTO
{
    /**
     * @var string User ID (numeric ID)
     */
    protected string $id = '';

    /**
     * @var string User name
     */
    protected string $name = '';

    /**
     * @var string User ID (string ID)
     */
    protected string $userId = '';

    /**
     * @var string Avatar URL
     */
    protected string $avatarUrl = '';

    /**
     * Create DTO from user entity.
     * @param mixed $userEntity
     */
    public static function fromUserEntity($userEntity): self
    {
        $dto = new self();
        $dto->setId((string) $userEntity->getId());
        $dto->setName($userEntity->getNickname());
        $dto->setUserId($userEntity->getUserId());
        $dto->setAvatarUrl($userEntity->getAvatarUrl() ?? '');

        return $dto;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->userId,
            'avatar_url' => $this->avatarUrl,
        ];
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

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
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
}
