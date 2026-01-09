<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Locker;

interface LockerInterface
{
    /**
     * get互斥lock
     * @param string $name lockname,finger定lockname
     * @param string $owner lock所have者,finger定lock唯oneidentifier,judgeerrorrelease
     * @param int $expire expiretime,second
     */
    public function mutexLock(string $name, string $owner, int $expire = 180): bool;

    /**
     * from旋lock
     * @param int $expire expiretime,unit:second
     */
    public function spinLock(string $name, string $owner, int $expire = 10): bool;

    /**
     * releaselock
     * @param string $name lockname,finger定lockname
     * @param string $owner lock所have者,finger定lock唯oneidentifier,judgeerrorrelease
     */
    public function release(string $name, string $owner): bool;
}
