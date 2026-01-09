<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\ValueObject;

/**
 * organization同status.
 */
enum OrganizationSyncStatus: int
{
    /* 未同 */
    case NotSynced = 0;

    /* 已同 */
    case Synced = 1;

    /* 同failed */
    case SyncFailed = 2;

    /* 同middle */
    case Syncing = 3;

    /**
     * whetherneed补偿。
     * andknowledge basestatus保持one致补偿set。
     */
    public static function needCompensate(): array
    {
        return [
            self::NotSynced,
            self::Syncing,
            self::SyncFailed,
        ];
    }
}
