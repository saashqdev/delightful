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
     * @param string $name lock的name，finger定lock的name
     * @param string $owner lock的所have者，finger定lock的唯一标识，判断errorrelease
     * @param int $expire expiretime，second
     */
    public function mutexLock(string $name, string $owner, int $expire = 180): bool;

    /**
     * 自旋lock
     * @param int $expire expiretime，unit:second
     */
    public function spinLock(string $name, string $owner, int $expire = 10): bool;

    /**
     * releaselock
     * @param string $name lock的name，finger定lock的name
     * @param string $owner lock的所have者，finger定lock的唯一标识，判断errorrelease
     */
    public function release(string $name, string $owner): bool;
}
