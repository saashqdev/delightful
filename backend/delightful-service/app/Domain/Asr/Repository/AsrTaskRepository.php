<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Repository;

use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Asr\Constants\AsrRedisKeys;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * ASR taskstatus仓储
 * 统一管理 Redis 中的taskstatus CRUD.
 */
readonly class AsrTaskRepository
{
    public function __construct(
        private Redis $redis,
        private LoggerInterface $logger
    ) {
    }

    /**
     * savetaskstatus到 Redis.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus DTO
     * @param int $ttl 过期time（秒），default 7 天
     */
    public function save(AsrTaskStatusDTO $taskStatus, int $ttl = AsrConfig::TASK_STATUS_TTL): void
    {
        try {
            $redisKey = $this->generateTaskKey($taskStatus->taskKey, $taskStatus->userId);

            // savetaskstatus数据
            $this->redis->hMSet($redisKey, $taskStatus->toArray());

            // set过期time
            $this->redis->expire($redisKey, $ttl);
        } catch (Throwable $e) {
            // Redis 操作fail时record但不抛出exception
            $this->logger->warning(trans('asr.api.redis.save_task_status_failed'), [
                'task_key' => $taskStatus->taskKey ?? 'unknown',
                'user_id' => $taskStatus->userId ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * according totask键和userIDquerytaskstatus.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return null|AsrTaskStatusDTO taskstatus DTO，不存在时return null
     */
    public function findByTaskKey(string $taskKey, string $userId): ?AsrTaskStatusDTO
    {
        try {
            $redisKey = $this->generateTaskKey($taskKey, $userId);
            $taskData = $this->redis->hGetAll($redisKey);

            if (empty($taskData)) {
                return null;
            }

            $dto = AsrTaskStatusDTO::fromArray($taskData);
            return $dto->isEmpty() ? null : $dto;
        } catch (Throwable $e) {
            $this->logger->warning('从 Redis gettaskstatusfail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * checktask是否存在.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return bool 是否存在
     */
    public function exists(string $taskKey, string $userId): bool
    {
        try {
            $redisKey = $this->generateTaskKey($taskKey, $userId);
            $result = $this->redis->exists($redisKey);
            return is_int($result) && $result > 0;
        } catch (Throwable $e) {
            $this->logger->warning('checktask是否存在fail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * deletetaskstatus.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return bool 是否deletesuccess
     */
    public function delete(string $taskKey, string $userId): bool
    {
        try {
            $redisKey = $this->generateTaskKey($taskKey, $userId);
            $result = $this->redis->del($redisKey);
            return is_int($result) && $result > 0;
        } catch (Throwable $e) {
            $this->logger->warning('deletetaskstatusfail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * delete心跳 Key.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     */
    public function deleteHeartbeat(string $taskKey, string $userId): void
    {
        try {
            $key = $this->generateHeartbeatKey($taskKey, $userId);
            $this->redis->del($key);
        } catch (Throwable $e) {
            $this->logger->warning('delete心跳 Key fail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 生成taskstatus的 Redis 键名.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return string Redis 键名
     */
    private function generateTaskKey(string $taskKey, string $userId): string
    {
        // 按统一规则生成string，然后 MD5 避免键名过长
        $keyString = sprintf('%s:%s', $userId, $taskKey);
        $keyHash = md5($keyString);
        return sprintf(AsrRedisKeys::TASK_HASH, $keyHash);
    }

    /**
     * 生成心跳 Key.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return string Redis 键名
     */
    private function generateHeartbeatKey(string $taskKey, string $userId): string
    {
        return sprintf(AsrRedisKeys::HEARTBEAT, md5(sprintf('%s:%s', $userId, $taskKey)));
    }
}
