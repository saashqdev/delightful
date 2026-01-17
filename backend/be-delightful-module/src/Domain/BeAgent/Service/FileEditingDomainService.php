<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;

/**
 * File editing status domain service
 */
class FileEditingDomainService
{
    private const REDIS_KEY_PREFIX = 'file_editing_status';

    private const TTL_SECONDS = 120; // 2 minutes

    private Redis $redis;

    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(Redis::class);
    }

    /**
     * Join editing.
     */
    public function joinEditing(int $fileId, string $userId, string $organizationCode): void
    {
        $key = $this->buildRedisKey($fileId, $organizationCode);

        // Add user to editing list
        $this->redis->sadd($key, $userId);
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Leave editing.
     */
    public function leaveEditing(int $fileId, string $userId, string $organizationCode): void
    {
        $key = $this->buildRedisKey($fileId, $organizationCode);

        // Remove user from editing list
        $this->redis->srem($key, $userId);

        // If no users are editing, delete the entire key
        if ($this->redis->scard($key) === 0) {
            $this->redis->del($key);
        }
    }

    /**
     * Get editing users count.
     */
    public function getEditingUsersCount(int $fileId, string $organizationCode): int
    {
        $key = $this->buildRedisKey($fileId, $organizationCode);

        // Return editing users count
        return $this->redis->scard($key);
    }

    /**
     * Build Redis key name.
     */
    public function buildRedisKey(int $fileId, string $organizationCode): string
    {
        return sprintf('%s:%s:%d', self::REDIS_KEY_PREFIX, $organizationCode, $fileId);
    }
}
