<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AdminGlobalSettingsType: int
{
    // all局default好友
    case DEFAULT_FRIEND = 1;

    // 助理create管理
    case ASSISTANT_CREATE = 2;

    // 第third-partypublish管控
    case THIRD_PARTY_PUBLISH = 3;

    // get助理all局settingtype
    public static function getAssistantGlobalSettingsType(): array
    {
        return [
            self::DEFAULT_FRIEND,
            self::ASSISTANT_CREATE,
            self::THIRD_PARTY_PUBLISH,
        ];
    }
}
