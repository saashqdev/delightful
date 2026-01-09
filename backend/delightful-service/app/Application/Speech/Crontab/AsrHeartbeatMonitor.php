<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Crontab;

use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Service\AsrFileAppService;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Asr\Constants\AsrRedisKeys;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Redis\RedisUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR 录音心跳monitor定时task.
 */
#[Crontab(
    rule: '* * * * *',                    // 每分钟执行一次
    name: 'AsrHeartbeatMonitor',
    singleton: true,                      // 单例模式防止重复执行
    mutexExpires: 60,                     // 互斥锁过期time（秒），对应 AsrConfig::HEARTBEAT_MONITOR_MUTEX_EXPIRES
    onOneServer: true,                    // 仅在一台service器上执行
    callback: 'execute',
    memo: 'ASR recording heartbeat monitoring task'
)]
class AsrHeartbeatMonitor
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly Redis $redis,
        private readonly AsrFileAppService $asrFileAppService,
        private readonly DelightfulUserDomainService $delightfulUserDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('AsrHeartbeatMonitor');
    }

    /**
     * 执行心跳monitortask.
     */
    public function execute(): void
    {
        try {
            $this->logger->info('开始执行 ASR 录音心跳monitortask');

            // 扫描所有心跳 key（use RedisUtil::scanKeys 防止阻塞）
            $keys = RedisUtil::scanKeys(
                AsrRedisKeys::HEARTBEAT_SCAN_PATTERN,
                AsrConfig::REDIS_SCAN_BATCH_SIZE,
                AsrConfig::REDIS_SCAN_MAX_COUNT
            );
            $timeoutCount = 0;

            foreach ($keys as $key) {
                try {
                    if ($this->checkHeartbeatTimeout($key)) {
                        ++$timeoutCount;
                    }
                } catch (Throwable $e) {
                    $this->logger->error('check心跳超时fail', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('ASR 录音心跳monitortask执行complete', [
                'timeout_count' => $timeoutCount,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('ASR 录音心跳monitortask执行fail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * check心跳是否超时.
     */
    private function checkHeartbeatTimeout(string $key): bool
    {
        // 读取心跳time戳
        $last = (int) $this->redis->get($key);
        // 超时阈value：90 秒
        if (($last > 0) && (time() - $last) <= AsrConfig::HEARTBEAT_TIMEOUT) {
            return false;
        }

        // Key 不存在或time戳超时，触发处理
        $this->handleHeartbeatTimeout($key);
        return true;
    }

    /**
     * 处理心跳超时.
     */
    private function handleHeartbeatTimeout(string $key): void
    {
        try {
            // 从 key 中提取 task_key 和 user_id
            // Key 格式：asr:heartbeat:{md5(user_id:task_key)}
            $this->logger->info('检测到心跳超时', ['key' => $key]);

            // 由于 key 是 MD5 hash，我们无法直接反向get task_key 和 user_id
            // 需要从 Redis 中扫描所有 asr:task:* 来查找匹配的task
            $this->findAndTriggerTimeoutTask($key);
        } catch (Throwable $e) {
            $this->logger->error('处理心跳超时fail', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 查找并触发超时task的自动总结.
     */
    private function findAndTriggerTimeoutTask(string $heartbeatKey): void
    {
        // 扫描所有task
        $keys = RedisUtil::scanKeys(
            AsrRedisKeys::TASK_SCAN_PATTERN,
            AsrConfig::REDIS_SCAN_BATCH_SIZE,
            AsrConfig::REDIS_SCAN_MAX_COUNT
        );

        foreach ($keys as $taskKey) {
            try {
                $taskData = $this->redis->hGetAll($taskKey);
                if (empty($taskData)) {
                    continue;
                }

                $taskStatus = AsrTaskStatusDTO::fromArray($taskData);

                // check是否匹配当前心跳 key
                $expectedHeartbeatKey = sprintf(
                    AsrRedisKeys::HEARTBEAT,
                    md5($taskStatus->userId . ':' . $taskStatus->taskKey)
                );

                if ($expectedHeartbeatKey === $heartbeatKey) {
                    // 找到匹配的task，check是否需要触发自动总结
                    if ($this->shouldTriggerAutoSummary($taskStatus)) {
                        $this->triggerAutoSummary($taskStatus);
                    }
                    return;
                }
            } catch (Throwable $e) {
                $this->logger->error('checktaskfail', [
                    'task_key' => $taskKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 判断是否应该触发自动总结.
     */
    private function shouldTriggerAutoSummary(AsrTaskStatusDTO $taskStatus): bool
    {
        // 如果已cancel，不触发
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            return false;
        }

        // 如果处于暂停status，不触发
        if ($taskStatus->isPaused) {
            return false;
        }

        // 如果录音status不是 start 或 recording，不触发
        if (! in_array($taskStatus->recordingStatus, [
            AsrRecordingStatusEnum::START->value,
            AsrRecordingStatusEnum::RECORDING->value,
        ], true)) {
            return false;
        }

        // 如果没有项目ID或话题ID，不触发
        if (empty($taskStatus->projectId) || empty($taskStatus->topicId)) {
            return false;
        }

        // 如果沙箱task未create，不触发
        if (! $taskStatus->sandboxTaskCreated) {
            return false;
        }

        return true;
    }

    /**
     * 触发自动总结.
     */
    private function triggerAutoSummary(AsrTaskStatusDTO $taskStatus): void
    {
        try {
            // 幂等性check：如果task已complete，跳过处理
            if ($taskStatus->isSummaryCompleted()) {
                $this->logger->info('task已complete，跳过心跳超时处理', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                    'status' => $taskStatus->status->value,
                ]);
                return;
            }

            $this->logger->info('触发心跳超时自动总结', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'project_id' => $taskStatus->projectId,
                'topic_id' => $taskStatus->topicId,
            ]);

            // getuser实体
            $userEntity = $this->delightfulUserDomainService->getUserById($taskStatus->userId);
            if ($userEntity === null) {
                ExceptionBuilder::throw(AsrErrorCode::UserNotExist);
            }

            $userAuthorization = DelightfulUserAuthorization::fromUserEntity($userEntity);
            $organizationCode = $taskStatus->organizationCode ?? $userAuthorization->getOrganizationCode();

            // 直接call自动总结method（会在method内部updatestatus）
            $this->asrFileAppService->autoTriggerSummary($taskStatus, $taskStatus->userId, $organizationCode);

            $this->logger->info('心跳超时自动总结已触发', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('触发自动总结fail', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
