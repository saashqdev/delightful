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
 * ASR recordingcorejumpmonitorscheduletask.
 */
#[Crontab(
    rule: '* * * * *',                    // eachminutesecondsexecuteonetime
    name: 'AsrHeartbeatMonitor',
    singleton: true,                      // singleexample modetypepreventduplicateexecute
    mutexExpires: 60,                     // mutually exclusivelockexpiretime(second),toshould AsrConfig::HEARTBEAT_MONITOR_MUTEX_EXPIRES
    onOneServer: true,                    // onlyinoneplatformservicedeviceupexecute
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
     * executecorejumpmonitortask.
     */
    public function execute(): void
    {
        try {
            $this->logger->info('startexecute ASR recordingcorejumpmonitortask');

            // scan allhavecorejump key(use RedisUtil::scanKeys preventblocking)
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
                    $this->logger->error('checkcorejumptimeoutfail', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('ASR recordingcorejumpmonitortaskexecutecomplete', [
                'timeout_count' => $timeoutCount,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('ASR recordingcorejumpmonitortaskexecutefail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * checkcorejumpwhethertimeout.
     */
    private function checkHeartbeatTimeout(string $key): bool
    {
        // readcorejumptimestamp
        $last = (int) $this->redis->get($key);
        // timeout阈value:90 second
        if (($last > 0) && (time() - $last) <= AsrConfig::HEARTBEAT_TIMEOUT) {
            return false;
        }

        // Key notexistsinortimestamptimeout,touchhairprocess
        $this->handleHeartbeatTimeout($key);
        return true;
    }

    /**
     * processcorejumptimeout.
     */
    private function handleHeartbeatTimeout(string $key): void
    {
        try {
            // from key middleextract task_key and user_id
            // Key format:asr:heartbeat:{md5(user_id:task_key)}
            $this->logger->info('detecttocorejumptimeout', ['key' => $key]);

            // byat key is MD5 hash,wenomethoddirectly反toget task_key and user_id
            // needfrom Redis middlescan allhave asr:task:* comefindmatchtask
            $this->findAndTriggerTimeoutTask($key);
        } catch (Throwable $e) {
            $this->logger->error('processcorejumptimeoutfail', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * findandtouchhairtimeouttaskfromautosummary.
     */
    private function findAndTriggerTimeoutTask(string $heartbeatKey): void
    {
        // scan allhavetask
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

                // checkwhethermatchcurrentcorejump key
                $expectedHeartbeatKey = sprintf(
                    AsrRedisKeys::HEARTBEAT,
                    md5($taskStatus->userId . ':' . $taskStatus->taskKey)
                );

                if ($expectedHeartbeatKey === $heartbeatKey) {
                    // findtomatchtask,checkwhetherneedtouchhairfromautosummary
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
     * judgewhethershouldtouchhairfromautosummary.
     */
    private function shouldTriggerAutoSummary(AsrTaskStatusDTO $taskStatus): bool
    {
        // ifalreadycancel,nottouchhair
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            return false;
        }

        // iflocationatpausestatus,nottouchhair
        if ($taskStatus->isPaused) {
            return false;
        }

        // ifrecordingstatusnotis start or recording,nottouchhair
        if (! in_array($taskStatus->recordingStatus, [
            AsrRecordingStatusEnum::START->value,
            AsrRecordingStatusEnum::RECORDING->value,
        ], true)) {
            return false;
        }

        // ifnothaveprojectIDortopicID,nottouchhair
        if (empty($taskStatus->projectId) || empty($taskStatus->topicId)) {
            return false;
        }

        // ifsandboxtasknotcreate,nottouchhair
        if (! $taskStatus->sandboxTaskCreated) {
            return false;
        }

        return true;
    }

    /**
     * touchhairfromautosummary.
     */
    private function triggerAutoSummary(AsrTaskStatusDTO $taskStatus): void
    {
        try {
            // poweretcpropertycheck:iftaskalreadycomplete,skipprocess
            if ($taskStatus->isSummaryCompleted()) {
                $this->logger->info('taskalreadycomplete,skipcorejumptimeoutprocess', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                    'status' => $taskStatus->status->value,
                ]);
                return;
            }

            $this->logger->info('touchhaircorejumptimeoutfromautosummary', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'project_id' => $taskStatus->projectId,
                'topic_id' => $taskStatus->topicId,
            ]);

            // getuseractualbody
            $userEntity = $this->delightfulUserDomainService->getUserById($taskStatus->userId);
            if ($userEntity === null) {
                ExceptionBuilder::throw(AsrErrorCode::UserNotExist);
            }

            $userAuthorization = DelightfulUserAuthorization::fromUserEntity($userEntity);
            $organizationCode = $taskStatus->organizationCode ?? $userAuthorization->getOrganizationCode();

            // directlycallfromautosummarymethod(willinmethodinsidedepartmentupdatestatus)
            $this->asrFileAppService->autoTriggerSummary($taskStatus, $taskStatus->userId, $organizationCode);

            $this->logger->info('corejumptimeoutfromautosummaryalreadytouchhair', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('touchhairfromautosummaryfail', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $taskStatus->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
