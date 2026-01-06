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
    case SuperDelightfulMCPServers = 'super_magic_mcp_servers';

    // 项目 mcp 用户配置
    case SuperDelightfulProjectMCPServers = 'SuperDelightfulProjectMCPServers';

    // 项目话题模型配置
    case SuperDelightfulProjectTopicModel = 'SuperDelightfulProjectTopicModel';

    // 用户当前组织
    case CurrentOrganization = 'CurrentOrganization';

    // 全局配置
    case GlobalConfig = 'GlobalConfig';

    // 平台设置（平台信息、Logo、Favicon、i18n 等）
    case PlatformSettings = 'PlatformSettings';

    // 智能体排序配置
    case SuperDelightfulAgentSort = 'SuperDelightfulAgentSort';

    public static function genSuperDelightfulProjectMCPServers(string $projectId): string
    {
        return self::SuperDelightfulProjectMCPServers->value . '_' . $projectId;
    }

    public static function genSuperDelightfulProjectTopicModel(string $topicId): string
    {
        return self::SuperDelightfulProjectTopicModel->value . '_' . $topicId;
    }

    public function getValueHandler(): ?UserSettingHandlerInterface
    {
        return match ($this) {
            self::SuperDelightfulMCPServers,self::SuperDelightfulProjectMCPServers => di(SuperDelightfulMCPServerHandler::class),
            self::SuperDelightfulProjectTopicModel => di(SuperDelightfulModelConfigHandler::class),
            default => null,
        };
    }

    public static function make(string $key): UserSettingKey
    {
        $userSettingKey = self::tryFrom($key);
        if ($userSettingKey) {
            return $userSettingKey;
        }

        if (str_starts_with($key, self::SuperDelightfulProjectMCPServers->value)) {
            return self::SuperDelightfulProjectMCPServers;
        }

        if (str_starts_with($key, self::SuperDelightfulProjectTopicModel->value)) {
            return self::SuperDelightfulProjectTopicModel;
        }

        return self::None;
    }

    public function isValid(): bool
    {
        return $this !== self::None;
    }
}
