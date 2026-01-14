<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Util;

use BeDelightful\TaskScheduler\Exception\TaskSchedulerException;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Throwable;

class Locker
{
    protected RedisProxy $redis;

    public function __construct(RedisFactory $redisFactory)
    {
        $this->redis = $redisFactory->get('default');
    }

    /**
     * Acquire a mutex lock.
     * @param string $name   Lock name used as the key
     * @param string $owner  Lock owner identifier to prevent accidental release
     * @param int    $expire Expiration time in seconds
     */
    public function mutexLock(string $name, string $owner, int $expire = 180): bool
    {
        try {
            return $this->redis->set($this->getLockKey($name), $owner, ['NX', 'EX' => $expire]);
        } catch (Throwable) {
            throw new TaskSchedulerException('lock error');
        }
    }

    public function release(string $name, string $owner): bool
    {
        try {
            $lua = <<<'EOT'
            if redis.call("get",KEYS[1]) == ARGV[1] then
                return redis.call("del",KEYS[1])
            else
                return 0
            end
            EOT;
            return (bool) $this->redis->eval($lua, [$this->getLockKey($name), $owner], 1);
        } catch (Throwable) {
            throw new TaskSchedulerException('release lock error');
        }
    }

    private function getLockKey(string $name): string
    {
        return 'task_scheduler_lock_' . $name;
    }
}
