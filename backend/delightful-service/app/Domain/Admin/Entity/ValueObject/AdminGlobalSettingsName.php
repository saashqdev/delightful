<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AdminGlobalSettingsName: string
{
    // 全局default好友
    case DEFAULT_FRIEND = 'default_friend';

    // 助理create管理
    case ASSISTANT_CREATE = 'create_management';

    // 第third-partypublish管控
    case THIRD_PARTY_PUBLISH = 'third_platform_publish';

    // get助理全局setting类型
    public static function getByType(AdminGlobalSettingsType $type): string
    {
        return match ($type) {
            AdminGlobalSettingsType::ASSISTANT_CREATE => self::ASSISTANT_CREATE->value,
            AdminGlobalSettingsType::THIRD_PARTY_PUBLISH => self::THIRD_PARTY_PUBLISH->value,
            AdminGlobalSettingsType::DEFAULT_FRIEND => self::DEFAULT_FRIEND->value,
        };
    }
}
