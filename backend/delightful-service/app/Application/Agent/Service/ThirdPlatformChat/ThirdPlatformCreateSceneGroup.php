<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat;

class ThirdPlatformCreateSceneGroup
{
    // group name
    private string $title;

    // group havepersonuserid
    private string $ownerUserId;

    // templateid
    private string $templateId;

    // memberlist
    private array $userIds;

    // administratorlist
    private array $subadminIds;

    // newmemberwhethercanviewhistorymessage
    private int $showHistoryType;

    // whethercansearchgroup chat, 0(default):notcansearch 1 search
    private int $searchable = 0;

    // join groupwhetherneedverify:0(default):notverify 1:join groupverify
    private int $validationType = 0;

    // @all userange: 0(default): havepersonallcan@all
    private int $mentionAllAuthority = 0;

    // groupmanagetype:0(default): havepersoncanmanage,1:onlygroup ownercanmanage
    private int $managementType = 0;

    // whetherstartgroupmute:0(default):notmute,1:allmember muted
    private int $chatBannedType;

    // groupuniqueoneidentifier
    private string $uuid;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setOwnerUserId(string $ownerUserId): void
    {
        $this->ownerUserId = $ownerUserId;
    }

    public function getOwnerUserId(): string
    {
        return $this->ownerUserId;
    }

    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function setSubadminIds(array $subadminIds): void
    {
        $this->subadminIds = $subadminIds;
    }

    public function getSubadminIds(): array
    {
        return $this->subadminIds;
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

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setTemplateId(string $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function getTemplateId(): string
    {
        return $this->templateId;
    }
}
