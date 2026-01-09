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
 * ASR 录音core跳monitorscheduletask.
 */
#[Crontab(
    rule: '* * * * *',                    // eachminute钟executeonetime
    name: 'AsrHeartbeatMonitor',
    singleton: true,                      // single例模typepreventduplicateexecute
    mutexExpires: 60,                     // 互斥lockexpiretime（second），to应 AsrConfig::HEARTBEAT_MONITOR_MUTEX_EXPIRES
    onOneServer: true,                    // 仅inone台service器upexecute
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
     * executecore跳monitortask.
     */
    public function execute(): void
    {
        try {
            $this->logger->info('startexecute ASR 录音core跳monitortask');

            // 扫描所havecore跳 key（use RedisUtil::scanKeys prevent阻塞）
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
                    $this->logger->error('checkcore跳timeoutfail', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('ASR 录音core跳monitortaskexecutecomplete', [
                'timeout_count' => $timeoutCount,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('ASR 录音core跳monitortaskexecutefail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * checkcore跳whethertimeout.
     */
    private function checkHeartbeatTimeout(string $key): bool
    {
        // readcore跳time戳
        $last = (int) $this->redis->get($key);
        // timeout阈value：90 second
        if (($last > 0) && (time() - $last) <= AsrConfig::HEARTBEAT_TIMEOUT) {
            return false;
        }

        // Key not存inortime戳timeout，触hairprocess
        $this->handleHeartbeatTimeout($key);
        return true;
    }

    /**
     * processcore跳timeout.
     */
    private function handleHeartbeatTimeout(string $key): void
    {
        try {
            // from key middleextract task_key and user_id
            // Key format：asr:heartbeat:{md5(user_id:task_key)}
            $this->logger->info('detecttocore跳timeout', ['key' => $key]);

            // byat key is MD5 hash，我们no法直接反toget task_key and user_id
            // needfrom Redis middle扫描所have asr:task:* comefindmatchtask
            $this->findAndTriggerTimeoutTask($key);
        } catch (Throwable $e) {
            $this->logger->error('processcore跳timeoutfail', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * findand触hairtimeouttaskfrom动总结.
     */
    private function findAndTriggerTimeoutTask(string $heartbeatKey): void
    {
        // 扫描所havetask
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

                // checkwhethermatchcurrentcore跳 key
                $expectedHeartbeatKey = sprintf(
                    AsrRedisKeys::HEARTBEAT,
                    md5($taskStatus->userId . ':' . $taskStatus->taskKey)
                );

                if ($expectedHeartbeatKey === $heartbeatKey) {
                    // 找tomatchtask，checkwhetherneed触hairfrom动总结
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
     * judgewhethershould触hairfrom动总结.
     */
    private function shouldTriggerAutoSummary(AsrTaskStatusDTO $taskStatus): bool
    {
        // ifalreadycancel，not触hair
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            return false;
        }

        // if处atpausestatus，not触hair
        if ($taskStatus->isPaused) {
            return false;
        }

        // if录音statusnotis start or recording，not触hair
        if (! in_array($taskStatus->recordingStatus, [
            AsrRecordingStatusEnum::START->value,
            AsrRecordingStatusEnum::RECORDING->value,
        ], true)) {
            return false;
        }

        // ifnothaveprojectIDor话题ID，not触hair
        if (empty($taskStatus->projectId) || empty($taskStatus->topicId)) {
            return false;
        }

        // if沙箱tasknotcreate，not触hair
        if (! $taskStatus->sandboxTaskCreated) {
            return false;
        }

        return true;
    }

    /**
     * 触hairfrom动总结.
     */
    private function triggerAutoSummary(AsrTaskStatusDTO $taskStatus): void
    {
        try {
            // poweretcpropertycheck：iftaskalreadycomplete，skipprocess
            if ($taskStatus->isSummaryCompleted()) {
                $this->logger->info('taskalreadycomplete，skipcore跳timeoutprocess', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                    'status' => $taskStatus->status->value,
                ]);
                return;
            }

            $this->logger->info('触haircore跳timeoutfrom动总结', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'project_id' => $taskStatus->projectId,
                'topic_id' => $taskStatus->topicId,
            ]);

            // getuser实body
            $userEntity = $this->delightfulUserDomainService->getUserById($taskStatus->userId);
            if ($userEntity === null) {
                ExceptionBuilder::throw(AsrErrorCode::UserNotExist);
            }

            $userAuthorization = DelightfulUserAuthorization::fromUserEntity($userEntity);
            $organizationCode = $taskStatus->organizationCode ?? $userAuthorization->getOrganizationCode();

            // 直接callfrom动总结method（willinmethodinside部updatestatus）
            $this->asrFileAppService->autoTriggerSummary($taskStatus, $taskStatus->userId, $organizationCode);

            $this->logger->info('core跳timeoutfrom动总结already触hair', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('触hairfrom动总结fail', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
