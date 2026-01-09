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
     * @param string $name lock的名称，指定lock的名称
     * @param string $owner lock的所有者，指定lock的唯一标识，判断error释放
     * @param int $expire 过期时间，秒
     */
    public function mutexLock(string $name, string $owner, int $expire = 180): bool;

    /**
     * 自旋lock
     * @param int $expire 过期时间，单位:秒
     */
    public function spinLock(string $name, string $owner, int $expire = 10): bool;

    /**
     * 释放lock
     * @param string $name lock的名称，指定lock的名称
     * @param string $owner lock的所有者，指定lock的唯一标识，判断error释放
     */
    public function release(string $name, string $owner): bool;
}
