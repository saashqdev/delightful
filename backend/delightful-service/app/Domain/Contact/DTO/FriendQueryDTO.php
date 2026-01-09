<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\DTO;

use App\Domain\Contact\Entity\AbstractEntity;
use App\Domain\Contact\Entity\ValueObject\UserType;

class FriendQueryDTO extends AbstractEntity
{
    // friend_type
    protected UserType $friendType = UserType::Ai;

    // 指定user ids,check是否是好友
    protected array $userIds = [];

    // 指定 ai codes,check是否是好友
    protected array $aiCodes = [];

    /**
     * 上一页的token. 对于mysql来说,return累积偏移量;对于es来说,return游标.
     */
    protected string $pageToken = '';

    // is_recursive 是否递归query
    protected bool $isRecursive = false;

    public function getFriendType(): UserType
    {
        return $this->friendType;
    }

    public function setFriendType(UserType $friendType): void
    {
        $this->friendType = $friendType;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    public function getAiCodes(): array
    {
        return $this->aiCodes;
    }

    public function setAiCodes(array $aiCodes): void
    {
        $this->aiCodes = $aiCodes;
    }

    public function getPageToken(): string
    {
        return $this->pageToken;
    }

    public function setPageToken(string $pageToken): void
    {
        $this->pageToken = $pageToken;
    }

    public function isRecursive(): bool
    {
        return $this->isRecursive;
    }

    public function setIsRecursive(bool $isRecursive): void
    {
        $this->isRecursive = $isRecursive;
    }
}
