<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\Assembler\AsrAssembler;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Hyperf\Codec\Json;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR presetfileservice
 * 负责createpreset笔记和stream识别file，供front端writecontent.
 */
readonly class AsrPresetFileService
{
    private LoggerInterface $logger;

    public function __construct(
        private ProjectDomainService $projectDomainService,
        private TaskFileDomainService $taskFileDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('AsrPresetFileService');
    }

    /**
     * createpreset笔记和stream识别file.
     *
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @param int $projectId projectID
     * @param string $displayDir displaydirectory相对path (如: 录音总结_xxx)
     * @param int $displayDirId displaydirectoryID
     * @param string $hiddenDir hiddendirectory相对path (如: .asr_recordings/session_xxx)
     * @param int $hiddenDirId hiddendirectoryID
     * @param string $taskKey task键
     * @return array{note_file: TaskFileEntity, transcript_file: TaskFileEntity}
     */
    public function createPresetFiles(
        string $userId,
        string $organizationCode,
        int $projectId,
        string $displayDir,
        int $displayDirId,
        string $hiddenDir,
        int $hiddenDirId,
        string $taskKey
    ): array {
        // getprojectinfo
        $projectEntity = $this->projectDomainService->getProject($projectId, $userId);
        $workDir = $projectEntity->getWorkDir();

        // getorganization码+APP_ID+bucket_md5front缀
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);

        // create笔记file（放indisplaydirectory，uservisible）
        $noteFile = $this->createNoteFile(
            $userId,
            $organizationCode,
            $projectId,
            $displayDir,
            $displayDirId,
            $taskKey,
            $fullPrefix,
            $workDir
        );

        // createstream识别file（放inhiddendirectory，usernotvisible）
        $transcriptFile = $this->createTranscriptFile(
            $userId,
            $organizationCode,
            $projectId,
            $hiddenDir,
            $hiddenDirId,
            $taskKey,
            $fullPrefix,
            $workDir
        );

        $this->logger->info('createpresetfilesuccess', [
            'task_key' => $taskKey,
            'note_file_id' => $noteFile->getFileId(),
            'transcript_file_id' => $transcriptFile->getFileId(),
        ]);

        return [
            'note_file' => $noteFile,
            'transcript_file' => $transcriptFile,
        ];
    }

    /**
     * delete笔记file（笔记content为空o clockcleanup）.
     *
     * @param string $fileId fileID
     * @return bool whetherdeletesuccess
     */
    public function deleteNoteFile(string $fileId): bool
    {
        try {
            $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
            if ($fileEntity === null) {
                $this->logger->warning('笔记filenot存in', ['file_id' => $fileId]);
                return false;
            }

            $this->taskFileDomainService->deleteById($fileEntity->getFileId());

            $this->logger->info('delete笔记filesuccess', [
                'file_id' => $fileId,
                'file_name' => $fileEntity->getFileName(),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('delete笔记filefail', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * deletestream识别file（总结completebackcleanup）.
     *
     * @param string $fileId fileID
     * @return bool whetherdeletesuccess
     */
    public function deleteTranscriptFile(string $fileId): bool
    {
        try {
            $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
            if ($fileEntity === null) {
                $this->logger->warning('stream识别filenot存in', ['file_id' => $fileId]);
                return false;
            }

            $this->taskFileDomainService->deleteById($fileEntity->getFileId());

            $this->logger->info('deletestream识别filesuccess', [
                'file_id' => $fileId,
                'file_name' => $fileEntity->getFileName(),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('deletestream识别filefail', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * create笔记file（放indisplaydirectory）.
     */
    private function createNoteFile(
        string $userId,
        string $organizationCode,
        int $projectId,
        string $displayDir,
        int $displayDirId,
        string $taskKey,
        string $fullPrefix,
        string $workDir
    ): TaskFileEntity {
        // ⚠️ use CoContext 和 di() getcorrect的language和翻译
        $language = CoContext::getLanguage();
        $translator = di(TranslatorInterface::class);
        $translator->setLocale($language);

        $fileName = $translator->trans('asr.file_names.preset_note') . '.md';
        $relativePath = rtrim($displayDir, '/') . '/' . $fileName;

        return $this->createPresetFile(
            userId: $userId,
            organizationCode: $organizationCode,
            projectId: $projectId,
            parentId: $displayDirId,
            fileName: $fileName,
            relativePath: $relativePath,
            fileType: 'note',
            isHidden: false,
            taskKey: $taskKey,
            fullPrefix: $fullPrefix,
            workDir: $workDir,
            logPrefix: 'preset笔记file'
        );
    }

    /**
     * createstream识别file（放inhiddendirectory）.
     */
    private function createTranscriptFile(
        string $userId,
        string $organizationCode,
        int $projectId,
        string $hiddenDir,
        int $hiddenDirId,
        string $taskKey,
        string $fullPrefix,
        string $workDir
    ): TaskFileEntity {
        // ⚠️ use CoContext 和 di() getcorrect的language和翻译
        $language = CoContext::getLanguage();
        $translator = di(TranslatorInterface::class);
        $translator->setLocale($language);

        $fileName = $translator->trans('asr.file_names.preset_transcript') . '.md';
        $relativePath = rtrim($hiddenDir, '/') . '/' . $fileName;

        return $this->createPresetFile(
            userId: $userId,
            organizationCode: $organizationCode,
            projectId: $projectId,
            parentId: $hiddenDirId,
            fileName: $fileName,
            relativePath: $relativePath,
            fileType: 'transcript',
            isHidden: true,
            taskKey: $taskKey,
            fullPrefix: $fullPrefix,
            workDir: $workDir,
            logPrefix: 'presetstream识别file'
        );
    }

    /**
     * createpresetfile的通usemethod.
     */
    private function createPresetFile(
        string $userId,
        string $organizationCode,
        int $projectId,
        int $parentId,
        string $fileName,
        string $relativePath,
        string $fileType,
        bool $isHidden,
        string $taskKey,
        string $fullPrefix,
        string $workDir,
        string $logPrefix
    ): TaskFileEntity {
        // 完整 file_key
        $fileKey = AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);

        // yuandata
        $metadata = [
            'asr_preset_file' => true,
            'file_type' => $fileType,
            'task_key' => $taskKey,
            'created_by' => 'asr_preset_file_service',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $taskFileEntity = new TaskFileEntity([
            'user_id' => $userId,
            'organization_code' => $organizationCode,
            'project_id' => $projectId,
            'topic_id' => 0,
            'task_id' => 0,
            'file_type' => 'user_upload',
            'file_name' => $fileName,
            'file_extension' => 'md',
            'file_key' => $fileKey,
            'file_size' => 0, // initial为0，front端writebackwillupdate
            'external_url' => '',
            'storage_type' => 'workspace',
            'is_hidden' => $isHidden,
            'is_directory' => false,
            'sort' => 0,
            'parent_id' => $parentId,
            'source' => 2, // 2-projectdirectory
            'metadata' => Json::encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->taskFileDomainService->insertOrIgnore($taskFileEntity);
        if ($result !== null) {
            return $result;
        }

        // ifinsertbeignore（file已存in），query现haverecord
        $existingFile = $this->taskFileDomainService->getByProjectIdAndFileKey($projectId, $fileKey);
        if ($existingFile !== null) {
            $this->logger->info(sprintf('%s已存in，use现haverecord', $logPrefix), [
                'task_key' => $taskKey,
                'file_id' => $existingFile->getFileId(),
            ]);
            return $existingFile;
        }

        ExceptionBuilder::throw(AsrErrorCode::CreatePresetFileFailed);
    }
}
