<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat;

class ThirdPlatformCreateGroup
{
    // 群名
    private string $name;

    // 群主的userid
    private string $owner;

    // memberlist
    private array $useridlist = [];

    // 新member是否可查看historymessage：1（default）：可查看，0：不可查看
    private int $showHistoryType = 1;

    // 是否可searchgroup chat, 0（default）：不可search 1：可search
    private int $searchable = 0;

    // 入群是否needverify：0（default）：不verify 1：入群verify
    private int $validationType = 0;

    // @all userange： 0（default）：所有人都can@all
    private int $mentionAllAuthority = 0;

    // 群管理type：0（default）：所有人可管理，1：仅群主可管理
    private int $managementType = 0;

    // 是否开启群禁言：0（default）：不禁言，1：全员禁言
    private int $chatBannedType = 0;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setUseridlist(array $useridlist): void
    {
        $this->useridlist = $useridlist;
    }

    public function getUseridlist(): array
    {
        return $this->useridlist;
    }

    public function setShowHistoryType(int $showHistoryType): void
    {
        $this->showHistoryType = $showHistoryType;
    }

    public function getShowHistoryType(): int
    {
        return $this->showHistoryType;
    }

    public function setSearchable(int $searchable): void
    {
        $this->searchable = $searchable;
    }

    public function getSearchable(): int
    {
        return $this->searchable;
    }

    public function setValidationType(int $validationType): void
    {
        $this->validationType = $validationType;
    }

    public function getValidationType(): int
    {
        return $this->validationType;
    }

    public function setMentionAllAuthority(int $mentionAllAuthority): void
    {
        $this->mentionAllAuthority = $mentionAllAuthority;
    }

    public function getMentionAllAuthority(): int
    {
        return $this->mentionAllAuthority;
    }

    public function setManagementType(int $managementType): void
    {
        $this->managementType = $managementType;
    }

    public function getManagementType(): int
    {
        return $this->managementType;
    }

    public function setChatBannedType(int $chatBannedType): void
    {
        $this->chatBannedType = $chatBannedType;
    }

    public function getChatBannedType(): int
    {
        return $this->chatBannedType;
    }
}
