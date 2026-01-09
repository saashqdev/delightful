<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\ValueObject;

/**
 * organizationsamestatus.
 */
enum OrganizationSyncStatus: int
{
    /* notsame */
    case NotSynced = 0;

    /* alreadysame */
    case Synced = 1;

    /* samefailed */
    case SyncFailed = 2;

    /* samemiddle */
    case Syncing = 3;

    /**
     * whetherneedcompensation.
     * andknowledge basestatusmaintainonetocompensationset.
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
