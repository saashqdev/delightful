<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Domain\Asr\Constants\AsrConfig;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR 沙箱responsehandle服务
 * 负责handle沙箱 finish 接口的response，updatefile和目录记录.
 */
readonly class AsrSandboxResponseHandler
{
    public function __construct(
        private AsrPresetFileService $presetFileService,
        private TaskFileDomainService $taskFileDomainService,
        private ProjectDomainService $projectDomainService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * handle沙箱 finish response，updatefile和目录记录.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $sandboxResponse 沙箱response数据（data 部分）
     */
    public function handleFinishResponse(
        AsrTaskStatusDTO $taskStatus,
        array $sandboxResponse,
    ): void {
        $this->logger->info('starthandle沙箱 finish response', [
            'task_key' => $taskStatus->taskKey,
            'response_keys' => array_keys($sandboxResponse),
        ]);

        // 1. 提取fileinfo
        $audioFile = $sandboxResponse['files']['audio_file'] ?? null;
        $noteFile = $sandboxResponse['files']['note_file'] ?? null;

        if ($audioFile === null) {
            $this->logger->warning('沙箱response中未找到audiofileinfo', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        // 2. check并handle目录重命名（沙箱有bug，will重命名目录但是没有notifyfile变动，没有改database记录）
        $taskStatus->displayDirectory = $this->extractDirectoryPath($audioFile);

        // 3. 查找audiofile记录
        $this->getAudioFileId($taskStatus, $audioFile);

        // 4. handle笔记file
        if ($noteFile !== null) {
            // pass file_key 查找最new笔记file ID（目录可能被重命名）
            $this->getNoteFileId($taskStatus, $noteFile);
        } else {
            // 笔记file为null或不存在，deletepreset的笔记file记录
            $this->handleEmptyNoteFile($taskStatus);
        }

        $this->logger->info('沙箱 finish responsehandlecomplete', [
            'task_key' => $taskStatus->taskKey,
            'audio_file_id' => $taskStatus->audioFileId,
            'note_file_id' => $taskStatus->noteFileId,
            'display_directory' => $taskStatus->displayDirectory,
        ]);
    }

    /**
     * 从file路径提取目录路径.
     *
     * @param array $fileInfo fileinfo
     * @return string 目录路径（工作区相对路径）
     */
    private function extractDirectoryPath(array $fileInfo): string
    {
        $filePath = $fileInfo['path'] ?? '';
        if (empty($filePath)) {
            return '';
        }

        // 从file路径提取actual的目录名
        return dirname($filePath);
    }

    /**
     * according toresponse的audiofile名/file路径，找到audiofile id，用于后续发chatmessage.
     * use轮询机制等待沙箱syncfile到database（at most等待 30 秒）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $audioFile audiofileinfo
     */
    private function getAudioFileId(
        AsrTaskStatusDTO $taskStatus,
        array $audioFile
    ): void {
        $relativePath = $audioFile['path'] ?? '';

        if (empty($relativePath)) {
            $this->logger->warning('audiofile路径为null，无法queryfile记录', [
                'task_key' => $taskStatus->taskKey,
                'audio_file' => $audioFile,
            ]);
            return;
        }

        try {
            $fileEntity = $this->findFileByPathWithPolling(
                $taskStatus,
                $relativePath,
                'audiofile'
            );

            if ($fileEntity !== null) {
                $taskStatus->audioFileId = (string) $fileEntity->getFileId();
                $taskStatus->filePath = $relativePath;
            }
        } catch (Throwable $e) {
            $this->logger->error('queryaudiofile记录fail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            // 如果是我们自己抛出的exception，直接重新抛出
            if ($e instanceof BusinessException) {
                throw $e;
            }

            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * according toresponse的笔记file路径，找到笔记file id.
     * use轮询机制等待沙箱syncfile到database（at most等待 30 秒）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $noteFile 笔记fileinfo
     */
    private function getNoteFileId(
        AsrTaskStatusDTO $taskStatus,
        array $noteFile
    ): void {
        $relativePath = $noteFile['path'] ?? '';

        if (empty($relativePath)) {
            $this->logger->warning('笔记file路径为null，清null笔记fileID', [
                'task_key' => $taskStatus->taskKey,
            ]);
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;
            return;
        }

        try {
            $fileEntity = $this->findFileByPathWithPolling(
                $taskStatus,
                $relativePath,
                '笔记file',
                false // 笔记filequeryfail不抛exception
            );

            if ($fileEntity !== null) {
                $taskStatus->noteFileId = (string) $fileEntity->getFileId();
                $taskStatus->noteFileName = $noteFile['filename'] ?? $noteFile['path'] ?? '';

                $this->logger->info('success找到笔记file记录', [
                    'task_key' => $taskStatus->taskKey,
                    'note_file_id' => $taskStatus->noteFileId,
                    'note_file_name' => $taskStatus->noteFileName,
                    'old_preset_note_file_id' => $taskStatus->presetNoteFileId,
                ]);
            } else {
                // 没找到就清null，不usepresetID
                $this->logger->warning('未找到笔记file记录', [
                    'task_key' => $taskStatus->taskKey,
                    'relative_path' => $relativePath,
                ]);
                $taskStatus->noteFileId = null;
                $taskStatus->noteFileName = null;
            }
        } catch (Throwable $e) {
            // 笔记filequeryfail，清null笔记fileinfo
            $this->logger->warning('query笔记file记录fail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;
        }
    }

    /**
     * passfile路径轮询queryfile记录（通用method）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $relativePath file相对路径
     * @param string $fileTypeName filetype名称（用于log）
     * @param bool $throwOnTimeout timeout是否抛出exception
     * @return null|TaskFileEntity file实体，未找到returnnull
     * @throws Throwable
     */
    private function findFileByPathWithPolling(
        AsrTaskStatusDTO $taskStatus,
        string $relativePath,
        string $fileTypeName,
        bool $throwOnTimeout = true
    ): ?TaskFileEntity {
        // check必要的taskstatus字段
        if (empty($taskStatus->projectId) || empty($taskStatus->userId) || empty($taskStatus->organizationCode)) {
            $this->logger->error('taskstatusinfo不完整，无法queryfile记录', [
                'task_key' => $taskStatus->taskKey,
                'file_type' => $fileTypeName,
                'project_id' => $taskStatus->projectId,
                'user_id' => $taskStatus->userId,
                'organization_code' => $taskStatus->organizationCode,
            ]);
            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => 'taskstatusinfo不完整']);
        }

        // get项目info并build file_key
        $projectEntity = $this->projectDomainService->getProject(
            (int) $taskStatus->projectId,
            $taskStatus->userId
        );
        $workDir = $projectEntity->getWorkDir();
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($taskStatus->organizationCode);
        $fileKey = AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);

        $this->logger->info(sprintf('start轮询query%s记录', $fileTypeName), [
            'task_key' => $taskStatus->taskKey,
            'file_type' => $fileTypeName,
            'relative_path' => $relativePath,
            'file_key' => $fileKey,
            'project_id' => $taskStatus->projectId,
            'max_wait_seconds' => AsrConfig::FILE_RECORD_QUERY_TIMEOUT,
        ]);

        // 轮询queryfile记录
        $timeoutSeconds = AsrConfig::FILE_RECORD_QUERY_TIMEOUT;
        $pollingInterval = AsrConfig::POLLING_INTERVAL;
        $startTime = microtime(true);
        $attempt = 0;

        while (true) {
            ++$attempt;
            $elapsedSeconds = (int) (microtime(true) - $startTime);

            // queryfile记录
            $existingFile = $this->taskFileDomainService->getByProjectIdAndFileKey(
                (int) $taskStatus->projectId,
                $fileKey
            );

            if ($existingFile !== null) {
                $this->logger->info(sprintf('success找到%s记录', $fileTypeName), [
                    'task_key' => $taskStatus->taskKey,
                    'file_type' => $fileTypeName,
                    'file_id' => $existingFile->getFileId(),
                    'file_name' => $existingFile->getFileName(),
                    'file_key' => $fileKey,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                ]);
                return $existingFile;
            }

            // check是否timeout
            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            // 记录轮询进度
            if ($attempt % AsrConfig::FILE_RECORD_QUERY_LOG_FREQUENCY === 0 || $attempt === 1) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info(sprintf('等待沙箱sync%s到database', $fileTypeName), [
                    'task_key' => $taskStatus->taskKey,
                    'file_type' => $fileTypeName,
                    'file_key' => $fileKey,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                    'remaining_seconds' => $remainingSeconds,
                ]);
            }

            // 等待下一次轮询
            sleep($pollingInterval);
        }

        // 轮询timeout，仍未找到file记录
        $totalElapsedTime = (int) (microtime(true) - $startTime);
        $this->logger->warning(sprintf('轮询timeout，未找到%s记录', $fileTypeName), [
            'task_key' => $taskStatus->taskKey,
            'file_type' => $fileTypeName,
            'file_key' => $fileKey,
            'relative_path' => $relativePath,
            'project_id' => $taskStatus->projectId,
            'total_attempts' => $attempt,
            'total_elapsed_seconds' => $totalElapsedTime,
            'timeout_seconds' => $timeoutSeconds,
        ]);

        if ($throwOnTimeout) {
            // 抛出exception
            ExceptionBuilder::throw(
                AsrErrorCode::CreateAudioFileFailed,
                '',
                ['error' => sprintf('等待 %d 秒后仍未找到%s记录', $timeoutSeconds, $fileTypeName)]
            );
        }

        return null;
    }

    /**
     * handlenull笔记file（deletepreset的笔记file记录）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     */
    private function handleEmptyNoteFile(AsrTaskStatusDTO $taskStatus): void
    {
        $noteFileId = $taskStatus->presetNoteFileId;
        if (empty($noteFileId)) {
            $this->logger->debug('preset笔记fileID为null，无需delete', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        $this->logger->info('笔记file为null或不存在，deletepreset笔记file记录', [
            'task_key' => $taskStatus->taskKey,
            'note_file_id' => $noteFileId,
        ]);

        $deleted = $this->presetFileService->deleteNoteFile($noteFileId);
        if ($deleted) {
            // 清nulltaskstatus中的笔记file相关字段
            $taskStatus->presetNoteFileId = null;
            $taskStatus->presetNoteFilePath = null;
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;

            $this->logger->info('null笔记filehandlecomplete', [
                'task_key' => $taskStatus->taskKey,
            ]);
        }
    }
}
