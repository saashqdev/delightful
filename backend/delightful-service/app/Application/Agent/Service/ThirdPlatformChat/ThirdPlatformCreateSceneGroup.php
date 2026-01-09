<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat;

class ThirdPlatformCreateSceneGroup
{
    // 群名
    private string $title;

    // 群所有者的userid
    private string $ownerUserId;

    // templateid
    private string $templateId;

    // 成员list
    private array $userIds;

    // 管理员list
    private array $subadminIds;

    // 新成员是否可查看历史message
    private int $showHistoryType;

    // 是否可searchgroup chat, 0（default）：不可search 1 search
    private int $searchable = 0;

    // 入群是否needverify：0（default）：不verify 1：入群verify
    private int $validationType = 0;

    // @all userange： 0（default）：所有人都can@all
    private int $mentionAllAuthority = 0;

    // 群管理type：0（default）：所有人可管理，1：仅群主可管理
    private int $managementType = 0;

    // 是否开启群禁言：0（default）：不禁言，1：全员禁言
    private int $chatBannedType;

    // group唯一标识
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
