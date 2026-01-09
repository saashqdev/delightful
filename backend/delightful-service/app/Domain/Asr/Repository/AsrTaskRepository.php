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
 * ASR taskstatusstorage
 * 统onemanage Redis middletaskstatus CRUD.
 */
readonly class AsrTaskRepository
{
    public function __construct(
        private Redis $redis,
        private LoggerInterface $logger
    ) {
    }

    /**
     * savetaskstatusto Redis.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus DTO
     * @param int $ttl expiretime(second),default 7 day
     */
    public function save(AsrTaskStatusDTO $taskStatus, int $ttl = AsrConfig::TASK_STATUS_TTL): void
    {
        try {
            $redisKey = $this->generateTaskKey($taskStatus->taskKey, $taskStatus->userId);

            // savetaskstatusdata
            $this->redis->hMSet($redisKey, $taskStatus->toArray());

            // setexpiretime
            $this->redis->expire($redisKey, $ttl);
        } catch (Throwable $e) {
            // Redis 操asfailo clockrecordbutnotthrowexception
            $this->logger->warning(trans('asr.api.redis.save_task_status_failed'), [
                'task_key' => $taskStatus->taskKey ?? 'unknown',
                'user_id' => $taskStatus->userId ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * according totaskkeyanduserIDquerytaskstatus.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return null|AsrTaskStatusDTO taskstatus DTO,not存ino clockreturn null
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
            $this->logger->warning('from Redis gettaskstatusfail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * checktaskwhether存in.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return bool whether存in
     */
    public function exists(string $taskKey, string $userId): bool
    {
        try {
            $redisKey = $this->generateTaskKey($taskKey, $userId);
            $result = $this->redis->exists($redisKey);
            return is_int($result) && $result > 0;
        } catch (Throwable $e) {
            $this->logger->warning('checktaskwhether存infail', [
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
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return bool whetherdeletesuccess
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
     * deletecore跳 Key.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     */
    public function deleteHeartbeat(string $taskKey, string $userId): void
    {
        try {
            $key = $this->generateHeartbeatKey($taskKey, $userId);
            $this->redis->del($key);
        } catch (Throwable $e) {
            $this->logger->warning('deletecore跳 Key fail', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * generatetaskstatus Redis key名.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return string Redis key名
     */
    private function generateTaskKey(string $taskKey, string $userId): string
    {
        // by统onerulegeneratestring,然back MD5 avoidkey名passlong
        $keyString = sprintf('%s:%s', $userId, $taskKey);
        $keyHash = md5($keyString);
        return sprintf(AsrRedisKeys::TASK_HASH, $keyHash);
    }

    /**
     * generatecore跳 Key.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return string Redis key名
     */
    private function generateHeartbeatKey(string $taskKey, string $userId): string
    {
        return sprintf(AsrRedisKeys::HEARTBEAT, md5(sprintf('%s:%s', $userId, $taskKey)));
    }
}
