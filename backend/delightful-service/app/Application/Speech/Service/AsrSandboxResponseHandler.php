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
 * ASR 沙箱responsehandleservice
 * 负责handle沙箱 finish interfaceresponse，updatefileanddirectoryrecord.
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
     * handle沙箱 finish response，updatefileanddirectoryrecord.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $sandboxResponse 沙箱responsedata（data 部minute）
     */
    public function handleFinishResponse(
        AsrTaskStatusDTO $taskStatus,
        array $sandboxResponse,
    ): void {
        $this->logger->info('starthandle沙箱 finish response', [
            'task_key' => $taskStatus->taskKey,
            'response_keys' => array_keys($sandboxResponse),
        ]);

        // 1. extractfileinfo
        $audioFile = $sandboxResponse['files']['audio_file'] ?? null;
        $noteFile = $sandboxResponse['files']['note_file'] ?? null;

        if ($audioFile === null) {
            $this->logger->warning('沙箱responsemiddle未找toaudiofileinfo', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        // 2. checkandhandledirectoryrename（沙箱havebug，willrenamedirectorybutisnothavenotifyfile变动，nothave改databaserecord）
        $taskStatus->displayDirectory = $this->extractDirectoryPath($audioFile);

        // 3. findaudiofilerecord
        $this->getAudioFileId($taskStatus, $audioFile);

        // 4. handle笔记file
        if ($noteFile !== null) {
            // pass file_key findmostnew笔记file ID（directorymaybeberename）
            $this->getNoteFileId($taskStatus, $noteFile);
        } else {
            // 笔记filefornullornot存in，deletepreset笔记filerecord
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
     * fromfilepathextractdirectorypath.
     *
     * @param array $fileInfo fileinfo
     * @return string directorypath（work区相topath）
     */
    private function extractDirectoryPath(array $fileInfo): string
    {
        $filePath = $fileInfo['path'] ?? '';
        if (empty($filePath)) {
            return '';
        }

        // fromfilepathextractactualdirectory名
        return dirname($filePath);
    }

    /**
     * according toresponseaudiofile名/filepath，找toaudiofile id，useatback续hairchatmessage.
     * useround询机制etc待沙箱syncfiletodatabase（at mostetc待 30 second）.
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
            $this->logger->warning('audiofilepathfornull，no法queryfilerecord', [
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
            $this->logger->error('queryaudiofilerecordfail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            // ifis我们from己throwexception，直接重新throw
            if ($e instanceof BusinessException) {
                throw $e;
            }

            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * according toresponse笔记filepath，找to笔记file id.
     * useround询机制etc待沙箱syncfiletodatabase（at mostetc待 30 second）.
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
            $this->logger->warning('笔记filepathfornull，清null笔记fileID', [
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
                false // 笔记filequeryfailnot抛exception
            );

            if ($fileEntity !== null) {
                $taskStatus->noteFileId = (string) $fileEntity->getFileId();
                $taskStatus->noteFileName = $noteFile['filename'] ?? $noteFile['path'] ?? '';

                $this->logger->info('success找to笔记filerecord', [
                    'task_key' => $taskStatus->taskKey,
                    'note_file_id' => $taskStatus->noteFileId,
                    'note_file_name' => $taskStatus->noteFileName,
                    'old_preset_note_file_id' => $taskStatus->presetNoteFileId,
                ]);
            } else {
                // not找tothen清null，notusepresetID
                $this->logger->warning('未找to笔记filerecord', [
                    'task_key' => $taskStatus->taskKey,
                    'relative_path' => $relativePath,
                ]);
                $taskStatus->noteFileId = null;
                $taskStatus->noteFileName = null;
            }
        } catch (Throwable $e) {
            // 笔记filequeryfail，清null笔记fileinfo
            $this->logger->warning('query笔记filerecordfail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;
        }
    }

    /**
     * passfilepathround询queryfilerecord（通usemethod）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $relativePath file相topath
     * @param string $fileTypeName filetypename（useatlog）
     * @param bool $throwOnTimeout timeoutwhetherthrowexception
     * @return null|TaskFileEntity file实body，未找toreturnnull
     * @throws Throwable
     */
    private function findFileByPathWithPolling(
        AsrTaskStatusDTO $taskStatus,
        string $relativePath,
        string $fileTypeName,
        bool $throwOnTimeout = true
    ): ?TaskFileEntity {
        // check必要taskstatusfield
        if (empty($taskStatus->projectId) || empty($taskStatus->userId) || empty($taskStatus->organizationCode)) {
            $this->logger->error('taskstatusinfonotcomplete，no法queryfilerecord', [
                'task_key' => $taskStatus->taskKey,
                'file_type' => $fileTypeName,
                'project_id' => $taskStatus->projectId,
                'user_id' => $taskStatus->userId,
                'organization_code' => $taskStatus->organizationCode,
            ]);
            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => 'taskstatusinfonotcomplete']);
        }

        // getprojectinfoandbuild file_key
        $projectEntity = $this->projectDomainService->getProject(
            (int) $taskStatus->projectId,
            $taskStatus->userId
        );
        $workDir = $projectEntity->getWorkDir();
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($taskStatus->organizationCode);
        $fileKey = AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);

        $this->logger->info(sprintf('startround询query%srecord', $fileTypeName), [
            'task_key' => $taskStatus->taskKey,
            'file_type' => $fileTypeName,
            'relative_path' => $relativePath,
            'file_key' => $fileKey,
            'project_id' => $taskStatus->projectId,
            'max_wait_seconds' => AsrConfig::FILE_RECORD_QUERY_TIMEOUT,
        ]);

        // round询queryfilerecord
        $timeoutSeconds = AsrConfig::FILE_RECORD_QUERY_TIMEOUT;
        $pollingInterval = AsrConfig::POLLING_INTERVAL;
        $startTime = microtime(true);
        $attempt = 0;

        while (true) {
            ++$attempt;
            $elapsedSeconds = (int) (microtime(true) - $startTime);

            // queryfilerecord
            $existingFile = $this->taskFileDomainService->getByProjectIdAndFileKey(
                (int) $taskStatus->projectId,
                $fileKey
            );

            if ($existingFile !== null) {
                $this->logger->info(sprintf('success找to%srecord', $fileTypeName), [
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

            // checkwhethertimeout
            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            // recordround询enterdegree
            if ($attempt % AsrConfig::FILE_RECORD_QUERY_LOG_FREQUENCY === 0 || $attempt === 1) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info(sprintf('etc待沙箱sync%stodatabase', $fileTypeName), [
                    'task_key' => $taskStatus->taskKey,
                    'file_type' => $fileTypeName,
                    'file_key' => $fileKey,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                    'remaining_seconds' => $remainingSeconds,
                ]);
            }

            // etc待downonetimeround询
            sleep($pollingInterval);
        }

        // round询timeout，仍未找tofilerecord
        $totalElapsedTime = (int) (microtime(true) - $startTime);
        $this->logger->warning(sprintf('round询timeout，未找to%srecord', $fileTypeName), [
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
            // throwexception
            ExceptionBuilder::throw(
                AsrErrorCode::CreateAudioFileFailed,
                '',
                ['error' => sprintf('etc待 %d secondback仍未找to%srecord', $timeoutSeconds, $fileTypeName)]
            );
        }

        return null;
    }

    /**
     * handlenull笔记file（deletepreset笔记filerecord）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     */
    private function handleEmptyNoteFile(AsrTaskStatusDTO $taskStatus): void
    {
        $noteFileId = $taskStatus->presetNoteFileId;
        if (empty($noteFileId)) {
            $this->logger->debug('preset笔记fileIDfornull，no需delete', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        $this->logger->info('笔记filefornullornot存in，deletepreset笔记filerecord', [
            'task_key' => $taskStatus->taskKey,
            'note_file_id' => $noteFileId,
        ]);

        $deleted = $this->presetFileService->deleteNoteFile($noteFileId);
        if ($deleted) {
            // 清nulltaskstatusmiddle笔记file相closefield
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
