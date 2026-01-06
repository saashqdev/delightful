<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\UserSetting;

enum UserSettingKey: string
{
    case None = 'none';

    // 全局 mcp 用户配置
    case BeDelightfulMCPServers = 'super_magic_mcp_servers';

    // 项目 mcp 用户配置
    case BeDelightfulProjectMCPServers = 'BeDelightfulProjectMCPServers';

    // 项目话题模型配置
    case BeDelightfulProjectTopicModel = 'BeDelightfulProjectTopicModel';

    // 用户当前组织
    case CurrentOrganization = 'CurrentOrganization';

    // 全局配置
    case GlobalConfig = 'GlobalConfig';

    // 平台设置（平台信息、Logo、Favicon、i18n 等）
    case PlatformSettings = 'PlatformSettings';

    // 智能体排序配置
    case BeDelightfulAgentSort = 'BeDelightfulAgentSort';

    public static function genBeDelightfulProjectMCPServers(string $projectId): string
    {
        return self::BeDelightfulProjectMCPServers->value . '_' . $projectId;
    }

    public static function genBeDelightfulProjectTopicModel(string $topicId): string
    {
        return self::BeDelightfulProjectTopicModel->value . '_' . $topicId;
    }

    public function getValueHandler(): ?UserSettingHandlerInterface
    {
        return match ($this) {
            self::BeDelightfulMCPServers,self::BeDelightfulProjectMCPServers => di(BeDelightfulMCPServerHandler::class),
            self::BeDelightfulProjectTopicModel => di(BeDelightfulModelConfigHandler::class),
            default => null,
        };
    }

    public static function make(string $key): UserSettingKey
    {
        $userSettingKey = self::tryFrom($key);
        if ($userSettingKey) {
            return $userSettingKey;
        }

        if (str_starts_with($key, self::BeDelightfulProjectMCPServers->value)) {
            return self::BeDelightfulProjectMCPServers;
        }

        if (str_starts_with($key, self::BeDelightfulProjectTopicModel->value)) {
            return self::BeDelightfulProjectTopicModel;
        }

        return self::None;
    }

    public function isValid(): bool
    {
        return $this !== self::None;
    }
}
