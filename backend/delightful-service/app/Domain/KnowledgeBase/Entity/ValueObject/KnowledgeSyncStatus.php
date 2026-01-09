<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum KnowledgeSyncStatus: int
{
    /*
     * not同
     */
    case NotSynced = 0;

    /*
     * 同middle
     */
    case Syncing = 3;

    /*
     * already同
     */
    case Synced = 1;

    /*
     * 同failed
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
     * 重建middle
     */
    case Rebuilding = 6;

    /*
     * needconduct补偿status
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
