<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum KnowledgeSyncStatus: int
{
    /*
     * notsame
     */
    case NotSynced = 0;

    /*
     * samemiddle
     */
    case Syncing = 3;

    /*
     * alreadysame
     */
    case Synced = 1;

    /*
     * samefailed
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
     * rebuildmiddle
     */
    case Rebuilding = 6;

    /*
     * needconductcompensationstatus
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
