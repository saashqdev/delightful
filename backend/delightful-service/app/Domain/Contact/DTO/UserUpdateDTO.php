<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\DTO;

use App\Domain\Contact\Entity\AbstractEntity;

class UserUpdateDTO extends AbstractEntity
{
    /**
     * useravatarURL.
     */
    protected ?string $avatarUrl = null;

    /**
     * user昵称.
     */
    protected ?string $nickname = null;

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    /**
     * convertforarrayformat,filter掉nullvalue
     */
    public function toUpdateArray(): array
    {
        $data = [];

        if ($this->avatarUrl !== null) {
            $data['avatar_url'] = $this->avatarUrl;
        }

        if ($this->nickname !== null) {
            $data['nickname'] = $this->nickname;
        }

        return $data;
    }
}
