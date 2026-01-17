<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Creator information DTO.
 */
class CreatorInfoDTO extends AbstractDTO
{
    /**
     * @var string User ID
     */
    protected string $userId = '';

    /**
     * @var string User nickname
     */
    protected string $nickname = '';

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
        $dto->setUserId($userEntity->getUserId());
        $dto->setNickname($userEntity->getNickname());
        $dto->setAvatarUrl($userEntity->getAvatarUrl() ?? '');

        return $dto;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'nickname' => $this->nickname,
            'avatar_url' => $this->avatarUrl,
        ];
    }

    // Getters and Setters
    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
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
