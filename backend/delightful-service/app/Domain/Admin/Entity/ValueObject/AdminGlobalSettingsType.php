<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AdminGlobalSettingsType: int
{
    // alllocaldefaultgoodfriend
    case DEFAULT_FRIEND = 1;

    // assistantcreatemanage
    case ASSISTANT_CREATE = 2;

    // thethird-partypublishcontrol
    case THIRD_PARTY_PUBLISH = 3;

    // getassistantalllocalsettingtype
    public static function getAssistantGlobalSettingsType(): array
    {
        return [
            self::DEFAULT_FRIEND,
            self::ASSISTANT_CREATE,
            self::THIRD_PARTY_PUBLISH,
        ];
    }
}
