<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Locker;

interface LockerInterface
{
    /**
     * getmutually exclusivelock
     * @param string $name lockname,fingersetlockname
     * @param string $owner lock haveperson,fingersetlockuniqueoneidentifier,judgeerrorrelease
     * @param int $expire expiretime,second
     */
    public function mutexLock(string $name, string $owner, int $expire = 180): bool;

    /**
     * fromrotatelock
     * @param int $expire expiretime,unit:second
     */
    public function spinLock(string $name, string $owner, int $expire = 10): bool;

    /**
     * releaselock
     * @param string $name lockname,fingersetlockname
     * @param string $owner lock haveperson,fingersetlockuniqueoneidentifier,judgeerrorrelease
     */
    public function release(string $name, string $owner): bool;
}
