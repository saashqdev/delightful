<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum KnowledgeSyncStatus: int
{
    /*
     * 未同步
     */
    case NotSynced = 0;

    /*
     * 同步中
     */
    case Syncing = 3;

    /*
     * 已同步
     */
    case Synced = 1;

    /*
     * 同步failed
     */
    case SyncFailed = 2;

    /*
     * deletesuccess
     */
    case Deleted = 4;

    /*
     * deletefailed
     */
    case DeleteFailed = 5;

    /*
     * 重建中
     */
    case Rebuilding = 6;

    /*
     * 需要进行补偿的status
     */
    public static function needCompensate(): array
    {
        return [
            self::NotSynced,
            self::Syncing,
            self::SyncFailed,
            self::Rebuilding,
        ];
    }
}
