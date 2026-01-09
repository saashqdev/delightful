<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\UserSetting;

enum UserSettingKey: string
{
    case None = 'none';

    // all局 mcp userconfiguration
    case BeDelightfulMCPServers = 'be_delightful_mcp_servers';

    // project mcp userconfiguration
    case BeDelightfulProjectMCPServers = 'BeDelightfulProjectMCPServers';

    // project话题modelconfiguration
    case BeDelightfulProjectTopicModel = 'BeDelightfulProjectTopicModel';

    // userwhenfrontorganization
    case CurrentOrganization = 'CurrentOrganization';

    // all局configuration
    case GlobalConfig = 'GlobalConfig';

    // 平台setting(平台information,Logo,Favicon,i18n etc)
    case PlatformSettings = 'PlatformSettings';

    // 智canbodysortconfiguration
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
