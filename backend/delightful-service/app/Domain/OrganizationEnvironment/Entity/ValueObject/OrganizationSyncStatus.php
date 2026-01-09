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
    /* not同 */
    case NotSynced = 0;

    /* already同 */
    case Synced = 1;

    /* 同failed */
    case SyncFailed = 2;

    /* 同middle */
    case Syncing = 3;

    /**
     * whetherneed补偿.
     * andknowledge basestatusmaintainone致补偿set.
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
