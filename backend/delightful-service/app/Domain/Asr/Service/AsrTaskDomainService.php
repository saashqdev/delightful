<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Service;

use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Domain\Asr\Constants\AsrRedisKeys;
use App\Domain\Asr\Repository\AsrTaskRepository;
use Hyperf\Redis\Redis;

/**
 * ASR task domain service
 * Responsible for ASR task status business logic.
 */
readonly class AsrTaskDomainService
{
    public function __construct(
        private AsrTaskRepository $asrTaskRepository,
        private Redis $redis
    ) {
    }

    /**
     * Save task status.
     *
     * @param AsrTaskStatusDTO $taskStatus task status DTO
     * @param int $ttl expiration time (seconds), default 7 days
     */
    public function saveTaskStatus(AsrTaskStatusDTO $taskStatus, int $ttl = 604800): void
    {
        $this->asrTaskRepository->save($taskStatus, $ttl);
    }

    /**
     * Query task status by task key and user ID.
     *
     * @param string $taskKey task key
     * @param string $userId user ID
     * @return null|AsrTaskStatusDTO task status DTO, return null if not exists
     */
    public function findTaskByKey(string $taskKey, string $userId): ?AsrTaskStatusDTO
    {
        return $this->asrTaskRepository->findByTaskKey($taskKey, $userId);
    }

    /**
     * Delete heartbeat key.
     *
     * @param string $taskKey task key
     * @param string $userId user ID
     */
    public function deleteTaskHeartbeat(string $taskKey, string $userId): void
    {
        $this->asrTaskRepository->deleteHeartbeat($taskKey, $userId);
    }

    /**
     * Atomic operation: save task status and set heartbeat
     * Use Redis MULTI/EXEC to ensure atomicity.
     *
     * @param AsrTaskStatusDTO $taskStatus task status DTO
     * @param int $taskTtl task status expiration time (seconds), default 7 days
     * @param int $heartbeatTtl heartbeat expiration time (seconds), default 5 minutes
     */
    public function saveTaskStatusWithHeartbeat(
        AsrTaskStatusDTO $taskStatus,
        int $taskTtl = 604800,
        int $heartbeatTtl = 300
    ): void {
        [$taskKey, $heartbeatKey] = $this->getRedisKeys($taskStatus);

        // Use MULTI/EXEC to ensure atomicity
        $this->redis->multi();
        $this->redis->hMSet($taskKey, $taskStatus->toArray());
        $this->redis->expire($taskKey, $taskTtl);
        $this->redis->setex($heartbeatKey, $heartbeatTtl, (string) time());
        $this->redis->exec();
    }

    /**
     * Atomic operation: save task status and delete heartbeat
     * Use Redis MULTI/EXEC to ensure atomicity.
     *
     * @param AsrTaskStatusDTO $taskStatus task status DTO
     * @param int $taskTtl task status expiration time (seconds), default 7 days
     */
    public function saveTaskStatusAndDeleteHeartbeat(
        AsrTaskStatusDTO $taskStatus,
        int $taskTtl = 604800
    ): void {
        [$taskKey, $heartbeatKey] = $this->getRedisKeys($taskStatus);

        // Use MULTI/EXEC to ensure atomicity
        $this->redis->multi();
        $this->redis->hMSet($taskKey, $taskStatus->toArray());
        $this->redis->expire($taskKey, $taskTtl);
        $this->redis->del($heartbeatKey);
        $this->redis->exec();
    }

    /**
     * Generate Redis keys (task status and heartbeat).
     *
     * @param AsrTaskStatusDTO $taskStatus task status DTO
     * @return array{0: string, 1: string} [task key, heartbeat key]
     */
    private function getRedisKeys(AsrTaskStatusDTO $taskStatus): array
    {
        $hash = md5($taskStatus->userId . ':' . $taskStatus->taskKey);
        return [
            sprintf(AsrRedisKeys::TASK_HASH, $hash),
            sprintf(AsrRedisKeys::HEARTBEAT, $hash),
        ];
    }
}
