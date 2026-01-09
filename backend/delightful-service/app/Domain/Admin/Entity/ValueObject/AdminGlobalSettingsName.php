<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AdminGlobalSettingsName: string
{
    // alllocaldefaultgoodfriend
    case DEFAULT_FRIEND = 'default_friend';

    // assistantcreatemanage
    case ASSISTANT_CREATE = 'create_management';

    // thethird-partypublishcontrol
    case THIRD_PARTY_PUBLISH = 'third_platform_publish';

    // getassistantalllocalsettingtype
    public static function getByType(AdminGlobalSettingsType $type): string
    {
        return match ($type) {
            AdminGlobalSettingsType::ASSISTANT_CREATE => self::ASSISTANT_CREATE->value,
            AdminGlobalSettingsType::THIRD_PARTY_PUBLISH => self::THIRD_PARTY_PUBLISH->value,
            AdminGlobalSettingsType::DEFAULT_FRIEND => self::DEFAULT_FRIEND->value,
        };
    }
}
