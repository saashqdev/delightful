<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\ValueObject;

/**
 * organization同步status.
 */
enum OrganizationSyncStatus: int
{
    /* 未同步 */
    case NotSynced = 0;

    /* 已同步 */
    case Synced = 1;

    /* 同步failed */
    case SyncFailed = 2;

    /* 同步中 */
    case Syncing = 3;

    /**
     * 是否需要补偿。
     * 与知识库status保持一致的补偿集合。
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
