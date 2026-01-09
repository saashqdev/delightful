<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat;

class ThirdPlatformCreateGroup
{
    // group name
    private string $name;

    // group owneruserid
    private string $owner;

    // memberlist
    private array $useridlist = [];

    // newmemberwhethercanviewhistorymessage:1(default):canview,0:notcanview
    private int $showHistoryType = 1;

    // whethercansearchgroup chat, 0(default):notcansearch 1:cansearch
    private int $searchable = 0;

    // join groupwhetherneedverify:0(default):notverify 1:join groupverify
    private int $validationType = 0;

    // @all userange: 0(default):所havepersonallcan@all
    private int $mentionAllAuthority = 0;

    // 群managetype:0(default):所havepersoncanmanage,1:onlygroup ownercanmanage
    private int $managementType = 0;

    // whetherstart群mute:0(default):notmute,1:allmember muted
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
