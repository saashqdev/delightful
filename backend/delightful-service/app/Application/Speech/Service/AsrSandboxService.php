<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\DTO\AsrSandboxMergeResultDTO;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\Enum\AsrTaskStatusEnum;
use App\Application\Speech\Enum\SandboxAsrStatusEnum;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\InitializationMetadataDTO;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Service\AgentDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\AsrRecorderInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response\AsrRecorderResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\ResponseCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * ASR 沙箱服务
 * 负责沙箱task启动、merge、轮询和file记录create.
 */
readonly class AsrSandboxService
{
    private LoggerInterface $logger;

    public function __construct(
        private SandboxGatewayInterface $sandboxGateway,
        private AsrRecorderInterface $asrRecorder,
        private AsrSandboxResponseHandler $responseHandler,
        private ProjectDomainService $projectDomainService,
        private TaskFileDomainService $taskFileDomainService,
        private AgentDomainService $agentDomainService,
        private TopicDomainService $topicDomainService,
        private TaskDomainService $taskDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('AsrSandboxService');
    }

    /**
     * 启动录音task.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $userId userID
     * @param string $organizationCode organization编码
     */
    public function startRecordingTask(
        AsrTaskStatusDTO $taskStatus,
        string $userId,
        string $organizationCode
    ): void {
        // generate沙箱ID
        $sandboxId = WorkDirectoryUtil::generateUniqueCodeFromSnowflakeId(
            $taskStatus->projectId . '_asr_recording',
            12
        );
        $taskStatus->sandboxId = $sandboxId;

        // settinguser上下文
        $this->sandboxGateway->setUserContext($userId, $organizationCode);

        // 获取完整工作目录路径
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

        // create沙箱并等待工作区可用
        $actualSandboxId = $this->ensureSandboxWorkspaceReady(
            $taskStatus,
            $sandboxId,
            $taskStatus->projectId,
            $fullWorkdir,
            $userId,
            $organizationCode
        );

        $this->logger->info('startRecordingTask ASR 录音：沙箱已就绪', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $sandboxId,
            'actual_sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // buildfileconfigurationobject（复用公共method）
        $noteFileConfig = $this->buildNoteFileConfig($taskStatus);
        $transcriptFileConfig = $this->buildTranscriptFileConfig($taskStatus);

        $this->logger->info('准备call沙箱 start 接口', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'temp_hidden_directory' => $taskStatus->tempHiddenDirectory,
            'workspace' => '.workspace',
            'note_file_config' => $noteFileConfig?->toArray(),
            'transcript_file_config' => $transcriptFileConfig?->toArray(),
        ]);

        // call沙箱启动task
        // 注意：沙箱 API 只接受工作区相对路径 (如: .asr_recordings/session_xxx)
        $response = $this->asrRecorder->startTask(
            $actualSandboxId,
            $taskStatus->taskKey,
            $taskStatus->tempHiddenDirectory,  // 如: .asr_recordings/session_xxx
            '.workspace',
            $noteFileConfig,
            $transcriptFileConfig
        );

        if (! $response->isSuccess()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxTaskCreationFailed, '', ['message' => $response->message]);
        }

        $taskStatus->sandboxTaskCreated = true;

        $this->logger->info('ASR 录音：沙箱task已create', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'status' => $response->getStatus(),
        ]);
    }

    /**
     * cancel录音task.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @return AsrRecorderResponse 响应结果
     */
    public function cancelRecordingTask(AsrTaskStatusDTO $taskStatus): AsrRecorderResponse
    {
        $sandboxId = $taskStatus->sandboxId;

        if (empty($sandboxId)) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        $this->logger->info('ASR 录音：准备cancel沙箱task', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
        ]);

        // call沙箱canceltask
        $response = $this->asrRecorder->cancelTask(
            $sandboxId,
            $taskStatus->taskKey
        );

        if (! $response->isSuccess()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxCancelFailed, '', ['message' => $response->message]);
        }

        $this->logger->info('ASR 录音：沙箱task已cancel', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
            'status' => $response->getStatus(),
        ]);

        return $response;
    }

    /**
     * mergeaudiofile.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $fileTitle file标题（not containextension名）
     * @param string $organizationCode organization编码
     * @return AsrSandboxMergeResultDTO merge结果
     */
    public function mergeAudioFiles(
        AsrTaskStatusDTO $taskStatus,
        string $fileTitle,
        string $organizationCode
    ): AsrSandboxMergeResultDTO {
        $this->logger->info('start沙箱audiohandleprocess', [
            'task_key' => $taskStatus->taskKey,
            'project_id' => $taskStatus->projectId,
            'hidden_directory' => $taskStatus->tempHiddenDirectory,
            'display_directory' => $taskStatus->displayDirectory,
            'sandbox_id' => $taskStatus->sandboxId,
        ]);

        // 准备沙箱ID
        if (empty($taskStatus->sandboxId)) {
            $sandboxId = WorkDirectoryUtil::generateUniqueCodeFromSnowflakeId(
                $taskStatus->projectId . '_asr_recording',
                12
            );
            $taskStatus->sandboxId = $sandboxId;
        }

        // settinguser上下文
        $this->sandboxGateway->setUserContext($taskStatus->userId, $organizationCode);

        // 获取完整工作目录路径
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $taskStatus->userId);
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

        $requestedSandboxId = $taskStatus->sandboxId;
        $actualSandboxId = $this->ensureSandboxWorkspaceReady(
            $taskStatus,
            $requestedSandboxId,
            $taskStatus->projectId,
            $fullWorkdir,
            $taskStatus->userId,
            $organizationCode
        );

        // 更新actual的沙箱ID（可能已经变化）
        if ($actualSandboxId !== $requestedSandboxId) {
            $this->logger->warning('沙箱ID发生变化，可能是沙箱重启', [
                'task_key' => $taskStatus->taskKey,
                'old_sandbox_id' => $requestedSandboxId,
                'new_sandbox_id' => $actualSandboxId,
            ]);
        }

        $this->logger->info('沙箱已就绪，准备call finish', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // call沙箱 finish 并轮询等待complete（willpass响应handle器自动create/更新file记录）
        $mergeResult = $this->callSandboxFinishAndWait($taskStatus, $fileTitle);

        $this->logger->info('沙箱return的fileinfo', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_file_path' => $mergeResult->filePath,
            'audio_file_id' => $taskStatus->audioFileId,
            'note_file_id' => $taskStatus->noteFileId,
        ]);

        // 更新taskstatus（file记录已由响应handle器create）
        $taskStatus->updateStatus(AsrTaskStatusEnum::COMPLETED);

        $this->logger->info('沙箱audiohandlecomplete', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $taskStatus->sandboxId,
            'file_id' => $taskStatus->audioFileId,
            'file_path' => $taskStatus->filePath,
        ]);

        return $mergeResult;
    }

    /**
     * call沙箱 finish 并轮询等待complete.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $intelligentTitle 智能标题（用于重命名）
     * @return AsrSandboxMergeResultDTO merge结果
     */
    private function callSandboxFinishAndWait(
        AsrTaskStatusDTO $taskStatus,
        string $intelligentTitle,
    ): AsrSandboxMergeResultDTO {
        $sandboxId = $taskStatus->sandboxId;

        if (empty($sandboxId)) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        // buildaudioconfigurationobject
        $audioConfig = new AsrAudioConfig(
            sourceDir: $taskStatus->tempHiddenDirectory,  // 如: .asr_recordings/session_xxx
            targetDir: $taskStatus->displayDirectory,     // 如: 录音总结_20251027_230949
            outputFilename: $intelligentTitle              // 如: 被讨厌的勇气
        );

        // build笔记fileconfigurationobject（need重命名）
        $noteFileConfig = $this->buildNoteFileConfig(
            $taskStatus,
            $taskStatus->displayDirectory,
            $intelligentTitle
        );

        // buildstream识别fileconfigurationobject
        $transcriptFileConfig = $this->buildTranscriptFileConfig($taskStatus);

        $this->logger->info('准备call沙箱 finish', [
            'task_key' => $taskStatus->taskKey,
            'intelligent_title' => $intelligentTitle,
            'audio_config' => $audioConfig->toArray(),
            'note_file_config' => $noteFileConfig?->toArray(),
            'transcript_file_config' => $transcriptFileConfig?->toArray(),
        ]);

        // 记录start时间
        $finishStartTime = microtime(true);

        // 首次call finish
        $response = $this->asrRecorder->finishTask(
            $sandboxId,
            $taskStatus->taskKey,
            '.workspace',
            $audioConfig,
            $noteFileConfig,
            $transcriptFileConfig
        );

        // 轮询等待complete（based onpreset时间与休眠间隔）
        $timeoutSeconds = AsrConfig::SANDBOX_MERGE_TIMEOUT;
        $pollingInterval = AsrConfig::POLLING_INTERVAL;
        $attempt = 0;
        $lastLogTime = $finishStartTime;
        $logInterval = AsrConfig::SANDBOX_MERGE_LOG_INTERVAL;

        while (true) {
            $elapsedSeconds = (int) (microtime(true) - $finishStartTime);

            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            ++$attempt;

            $statusString = $response->getStatus();
            $status = SandboxAsrStatusEnum::from($statusString);

            // checkcompletestatus或errorstatus
            $result = $this->checkAndHandleResponseStatus(
                $response,
                $status,
                $taskStatus,
                $sandboxId,
                $finishStartTime,
                $attempt
            );
            if ($result !== null) {
                return $result;
            }

            // 中间status（waiting, running, finalizing）：continue轮询并按间隔记录进度
            $currentTime = microtime(true);
            $elapsedSeconds = (int) ($currentTime - $finishStartTime);
            if ($attempt % AsrConfig::SANDBOX_MERGE_LOG_FREQUENCY === 0 || ($currentTime - $lastLogTime) >= $logInterval) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info('等待沙箱audiomerge', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $sandboxId,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                    'remaining_seconds' => $remainingSeconds,
                    'status' => $status->value ?? $statusString,
                    'status_description' => $status->getDescription(),
                ]);
                $lastLogTime = $currentTime;
            }

            // 时间不足，不再 sleep，直接进行最后一次 finishTask
            if (($elapsedSeconds + $pollingInterval) >= $timeoutSeconds) {
                break;
            }

            sleep($pollingInterval);

            // continue轮询
            $response = $this->asrRecorder->finishTask(
                $sandboxId,
                $taskStatus->taskKey,
                '.workspace',
                $audioConfig,
                $noteFileConfig,
                $transcriptFileConfig
            );
        }

        // 时间即将耗尽，进行最后一次check
        $statusString = $response->getStatus();
        $status = SandboxAsrStatusEnum::from($statusString);
        $result = $this->checkAndHandleResponseStatus(
            $response,
            $status,
            $taskStatus,
            $sandboxId,
            $finishStartTime,
            $attempt
        );
        if ($result !== null) {
            return $result;
        }

        // 超时记录
        $totalElapsedTime = (int) (microtime(true) - $finishStartTime);
        $this->logger->error('沙箱audiomerge超时', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
            'total_attempts' => $attempt,
            'total_elapsed_seconds' => $totalElapsedTime,
            'timeout_seconds' => $timeoutSeconds,
            'last_status' => $status->value ?? $statusString,
        ]);

        ExceptionBuilder::throw(AsrErrorCode::SandboxMergeTimeout);
    }

    /**
     * check并handle沙箱响应status.
     *
     * @param AsrRecorderResponse $response 沙箱响应
     * @param SandboxAsrStatusEnum $status status枚举
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $sandboxId 沙箱ID
     * @param float $finishStartTime start时间
     * @param int $attempt 尝试次数
     * @return null|AsrSandboxMergeResultDTO 如果complete则return结果，否则returnnull
     * @throws BusinessException 如果是errorstatus则抛出exception
     */
    private function checkAndHandleResponseStatus(
        AsrRecorderResponse $response,
        SandboxAsrStatusEnum $status,
        AsrTaskStatusDTO $taskStatus,
        string $sandboxId,
        float $finishStartTime,
        int $attempt
    ): ?AsrSandboxMergeResultDTO {
        // check是否为completestatus（contain completed 和 finished）
        if ($status->isCompleted()) {
            // 计算总耗时
            $finishEndTime = microtime(true);
            $totalElapsedTime = round($finishEndTime - $finishStartTime);

            $this->logger->info('沙箱audiomergecomplete', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $sandboxId,
                'attempt' => $attempt,
                'status' => $status->value,
                'file_path' => $response->getFilePath(),
                'total_elapsed_time_seconds' => $totalElapsedTime,
            ]);

            // handle沙箱响应，更新file和目录记录
            $responseData = $response->getData();
            $this->responseHandler->handleFinishResponse(
                $taskStatus,
                $responseData,
            );

            return AsrSandboxMergeResultDTO::fromSandboxResponse([
                'status' => $status->value,
                'file_path' => $response->getFilePath(),
                'duration' => $response->getDuration(),
                'file_size' => $response->getFileSize(),
            ]);
        }

        // check是否为errorstatus
        if ($status->isError()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxMergeFailed, '', ['message' => $response->getErrorMessage()]);
        }

        return null;
    }

    /**
     * 等待沙箱启动（can响应接口）.
     * ASR 功能不need工作区initialize，只need沙箱can响应 getWorkspaceStatus 接口即可.
     *
     * @param string $sandboxId 沙箱ID
     * @param string $taskKey taskKey（用于log）
     * @throws BusinessException 当超时时抛出exception
     */
    private function waitForSandboxStartup(
        string $sandboxId,
        string $taskKey
    ): void {
        $this->logger->info('ASR 录音：等待沙箱启动', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
            'interval_seconds' => AsrConfig::POLLING_INTERVAL,
        ]);

        $startTime = time();
        $endTime = $startTime + AsrConfig::SANDBOX_STARTUP_TIMEOUT;

        while (time() < $endTime) {
            try {
                // 尝试获取工作区status，只要接口能successreturn就说明沙箱已启动
                $response = $this->agentDomainService->getWorkspaceStatus($sandboxId);
                $status = $response->getDataValue('status');

                $this->logger->info('ASR 录音：沙箱已启动并可响应', [
                    'task_key' => $taskKey,
                    'sandbox_id' => $sandboxId,
                    'status' => $status,
                    'status_description' => WorkspaceStatus::getDescription($status),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // 接口successreturn，沙箱已启动
                return;
            } catch (Throwable $e) {
                // 接口callfail，说明沙箱还未启动，continue等待
                $this->logger->debug('ASR 录音：沙箱尚未启动，continue等待', [
                    'task_key' => $taskKey,
                    'sandbox_id' => $sandboxId,
                    'error' => $e->getMessage(),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // 等待下一次轮询
                sleep(AsrConfig::POLLING_INTERVAL);
            }
        }

        // 超时
        $this->logger->error('ASR 录音：等待沙箱启动超时', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
        ]);

        ExceptionBuilder::throw(
            AsrErrorCode::SandboxTaskCreationFailed,
            '',
            ['message' => '等待沙箱启动超时（' . AsrConfig::SANDBOX_STARTUP_TIMEOUT . '秒）']
        );
    }

    /**
     * pass AgentDomainService create沙箱并等待工作区就绪.
     */
    private function ensureSandboxWorkspaceReady(
        AsrTaskStatusDTO $taskStatus,
        string $requestedSandboxId,
        ?string $projectId,
        string $fullWorkdir,
        string $userId,
        string $organizationCode
    ): string {
        if ($requestedSandboxId === '') {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        $projectIdString = (string) $projectId;
        if ($projectIdString === '') {
            ExceptionBuilder::throw(AsrErrorCode::SandboxTaskCreationFailed, '', ['message' => '项目ID为null，无法create沙箱']);
        }

        // 尝试获取工作区status
        $workspaceStatusResponse = null;
        try {
            $workspaceStatusResponse = $this->agentDomainService->getWorkspaceStatus($requestedSandboxId);
        } catch (Throwable $throwable) {
            $this->logger->warning('获取沙箱工作区statusfail，沙箱可能未启动，将create新沙箱', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $requestedSandboxId,
                'error' => $throwable->getMessage(),
            ]);
        }

        // 如果工作区status响应存在，check是否needinitialize
        if ($workspaceStatusResponse !== null) {
            $responseCode = $workspaceStatusResponse->getCode();
            $workspaceStatus = (int) $workspaceStatusResponse->getDataValue('status');

            // 如果响应success（code 1000）且工作区已就绪，直接return
            if ($responseCode === ResponseCode::SUCCESS && WorkspaceStatus::isReady($workspaceStatus)) {
                $this->logger->info('检测到沙箱工作区已就绪，无需initialize', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $requestedSandboxId,
                    'status' => $workspaceStatus,
                    'status_description' => WorkspaceStatus::getDescription($workspaceStatus),
                ]);

                $taskStatus->sandboxId = $requestedSandboxId;

                return $requestedSandboxId;
            }

            // 如果响应success但工作区未initialize，或响应fail，needinitialize工作区
            $this->logger->info('检测到沙箱工作区未initialize或响应exception，needinitialize工作区', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $requestedSandboxId,
                'response_code' => $responseCode,
                'workspace_status' => $workspaceStatus,
                'status_description' => WorkspaceStatus::getDescription($workspaceStatus),
            ]);

            $taskStatus->sandboxId = $requestedSandboxId;
            $this->initializeWorkspace($taskStatus, $requestedSandboxId, $userId, $organizationCode);

            return $requestedSandboxId;
        }

        // 工作区status响应不存在，沙箱未启动，needcreate沙箱
        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        $this->logger->info('准备call AgentDomainService create沙箱', [
            'task_key' => $taskStatus->taskKey,
            'project_id' => $projectIdString,
            'requested_sandbox_id' => $requestedSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        $actualSandboxId = $this->agentDomainService->createSandbox(
            $dataIsolation,
            $projectIdString,
            $requestedSandboxId,
            $fullWorkdir
        );

        $this->logger->info('沙箱create请求complete，等待沙箱启动', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $requestedSandboxId,
            'actual_sandbox_id' => $actualSandboxId,
        ]);

        // 等待沙箱启动（can响应接口）
        $this->waitForSandboxStartup($actualSandboxId, $taskStatus->taskKey);

        $taskStatus->sandboxId = $actualSandboxId;

        // initialize工作区
        $this->initializeWorkspace($taskStatus, $actualSandboxId, $userId, $organizationCode);

        return $actualSandboxId;
    }

    /**
     * initialize工作区.
     * 复用 AgentDomainService::initializeAgent method，ensureinitializeconfiguration一致.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $actualSandboxId actual沙箱ID
     * @param string $userId userID
     * @param string $organizationCode organization编码
     */
    private function initializeWorkspace(
        AsrTaskStatusDTO $taskStatus,
        string $actualSandboxId,
        string $userId,
        string $organizationCode
    ): void {
        $this->logger->info('沙箱已启动，准备sendinitializemessage', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'topic_id' => $taskStatus->topicId,
        ]);

        // 获取或create Task Entity（用于build TaskContext）
        $taskEntity = $this->getOrCreateTaskEntity($taskStatus, $userId, $organizationCode);

        $this->logger->info('获取到 ASR task Entity', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'task_id' => $taskEntity->getId(),
        ]);

        // create DataIsolation
        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        // 获取 topic 实体（用于获取 workspaceId 等info）
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => '话题不存在']
            );
        }

        // 获取项目实体（用于获取项目organization编码）
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $projectOrganizationCode = $projectEntity->getUserOrganizationCode();

        // 确定 agentUserId：use topic 的create者ID，如果没有则use topic 的userID（参考 AgentAppService）
        $agentUserId = $topicEntity->getCreatedUid() ?: $topicEntity->getUserId();

        // build TaskContext（ASR 场景中 chatConversationId、chatTopicId usenullstring）
        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: '', // ASR 场景不needchatconversationID
            chatTopicId: '', // ASR 场景不needchatthemeID
            agentUserId: $agentUserId, // use topic 的create者ID或userID
            sandboxId: $actualSandboxId,
            taskId: (string) $taskEntity->getId(),
            instruction: ChatInstruction::Normal,
            agentMode: $topicEntity->getTaskMode() ?: 'general',
            workspaceId: (string) $topicEntity->getWorkspaceId(),
            isFirstTask: false, // ASR 场景通常不是首次task
        );

        // 复用 initializeAgent method（will自动build message_subscription_config 和 delightful_service_host）
        // 传入项目organization编码，用于获取correct的 STS Token
        // ASR 场景setting skip_init_messages = true，让沙箱不sendchatmessage过来
        $initMetadata = (new InitializationMetadataDTO(skipInitMessages: true));
        $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, null, $projectOrganizationCode, $initMetadata);

        $this->logger->info('沙箱initializemessage已send，等待工作区initializecomplete', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // 等待工作区initializecomplete（includefilesync）
        $this->agentDomainService->waitForWorkspaceReady(
            $actualSandboxId,
            AsrConfig::WORKSPACE_INIT_TIMEOUT,
            AsrConfig::POLLING_INTERVAL
        );

        $this->logger->info('沙箱工作区已initializecomplete，file已sync，canstartuse', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // 更新话题status为已complete（match DDD 分层，pass Domain Service 操作）
        $this->topicDomainService->updateTopicStatus(
            (int) $taskStatus->topicId,
            $taskEntity->getId(),
            TaskStatus::FINISHED
        );

        $this->logger->info('话题status已更新为 finished', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'task_id' => $taskEntity->getId(),
        ]);
    }

    /**
     * build笔记fileconfigurationobject.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param null|string $targetDirectory 目标目录（可选，default与源目录same）
     * @param null|string $intelligentTitle 智能标题（可选，用于重命名）
     */
    private function buildNoteFileConfig(
        AsrTaskStatusDTO $taskStatus,
        ?string $targetDirectory = null,
        ?string $intelligentTitle = null
    ): ?AsrNoteFileConfig {
        if (empty($taskStatus->presetNoteFilePath)) {
            return null;
        }

        $workspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath($taskStatus->presetNoteFilePath);

        // 如果未指定目标目录，use源路径（不重命名）
        if ($targetDirectory === null || $intelligentTitle === null) {
            return new AsrNoteFileConfig(
                sourcePath: $workspaceRelativePath,
                targetPath: $workspaceRelativePath
            );
        }

        // need重命名：use智能标题和国际化的笔记后缀build目标路径
        $fileExtension = pathinfo($workspaceRelativePath, PATHINFO_EXTENSION);
        $noteSuffix = trans('asr.file_names.note_suffix'); // according to语言获取国际化的"笔记"/"Note"
        $noteFilename = sprintf('%s-%s.%s', $intelligentTitle, $noteSuffix, $fileExtension);

        return new AsrNoteFileConfig(
            sourcePath: $workspaceRelativePath,
            targetPath: rtrim($targetDirectory, '/') . '/' . $noteFilename
        );
    }

    /**
     * buildstream识别fileconfigurationobject.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     */
    private function buildTranscriptFileConfig(AsrTaskStatusDTO $taskStatus): ?AsrTranscriptFileConfig
    {
        if (empty($taskStatus->presetTranscriptFilePath)) {
            return null;
        }

        $transcriptWorkspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath(
            $taskStatus->presetTranscriptFilePath
        );

        return new AsrTranscriptFileConfig(
            sourcePath: $transcriptWorkspaceRelativePath
        );
    }

    /**
     * 获取或create Task Entity（用于build TaskContext）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $userId userID
     * @param string $organizationCode organization编码
     * @return TaskEntity Task Entity
     */
    private function getOrCreateTaskEntity(
        AsrTaskStatusDTO $taskStatus,
        string $userId,
        string $organizationCode
    ): TaskEntity {
        // check topicId 是否存在
        if (empty($taskStatus->topicId)) {
            $this->logger->error('ASR task缺少 topicId，无法获取或create task', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'Topic ID 为null，无法create沙箱task']
            );
        }

        // 获取 topic 实体
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            $this->logger->error('ASR task关联的 topic 不存在', [
                'task_key' => $taskStatus->taskKey,
                'topic_id' => $taskStatus->topicId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => '话题不存在']
            );
        }

        // check topic 是否有currenttask
        $currentTaskId = $topicEntity->getCurrentTaskId();
        if ($currentTaskId !== null && $currentTaskId > 0) {
            $taskEntity = $this->taskDomainService->getTaskById($currentTaskId);
            if ($taskEntity) {
                $this->logger->info('ASR taskuse topic 的currenttask Entity', [
                    'task_key' => $taskStatus->taskKey,
                    'topic_id' => $taskStatus->topicId,
                    'current_task_id' => $currentTaskId,
                ]);
                return $taskEntity;
            }
        }

        // topic 没有currenttask，为 ASR 场景create一个新task
        $this->logger->info('ASR task关联的 topic 没有currenttask，准备create新task', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'project_id' => $taskStatus->projectId,
        ]);

        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        $taskData = [
            'user_id' => $userId,
            'workspace_id' => $topicEntity->getWorkspaceId(),
            'project_id' => $topicEntity->getProjectId(),
            'topic_id' => $topicEntity->getId(),
            'task_id' => '', // 数据库will自动generate
            'task_mode' => $topicEntity->getTaskMode() ?: 'general',
            'sandbox_id' => $topicEntity->getSandboxId() ?: '',
            'prompt' => 'ASR Recording Task', // ASR task标识
            'task_status' => 'waiting',
            'work_dir' => $topicEntity->getWorkDir() ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $taskEntity = TaskEntity::fromArray($taskData);

        // createtask并更新 topic
        $createdTask = $this->taskDomainService->initTopicTask($dataIsolation, $topicEntity, $taskEntity);

        $this->logger->info('为 ASR taskcreate了new task', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'created_task_id' => $createdTask->getId(),
        ]);

        return $createdTask;
    }
}
