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
 * ASR 预设文件service
 * 负责create预设笔记和流式识别文件，供前端写入content.
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
     * create预设笔记和流式识别文件.
     *
     * @param string $userId userID
     * @param string $organizationCode organization编码
     * @param int $projectId 项目ID
     * @param string $displayDir 显示目录相对路径 (如: 录音总结_xxx)
     * @param int $displayDirId 显示目录ID
     * @param string $hiddenDir 隐藏目录相对路径 (如: .asr_recordings/session_xxx)
     * @param int $hiddenDirId 隐藏目录ID
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
        // get项目info
        $projectEntity = $this->projectDomainService->getProject($projectId, $userId);
        $workDir = $projectEntity->getWorkDir();

        // getorganization码+APP_ID+bucket_md5前缀
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);

        // create笔记文件（放在显示目录，user可见）
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

        // create流式识别文件（放在隐藏目录，user不可见）
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

        $this->logger->info('create预设文件success', [
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
     * delete笔记文件（笔记content为空时清理）.
     *
     * @param string $fileId 文件ID
     * @return bool 是否deletesuccess
     */
    public function deleteNoteFile(string $fileId): bool
    {
        try {
            $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
            if ($fileEntity === null) {
                $this->logger->warning('笔记文件不存在', ['file_id' => $fileId]);
                return false;
            }

            $this->taskFileDomainService->deleteById($fileEntity->getFileId());

            $this->logger->info('delete笔记文件success', [
                'file_id' => $fileId,
                'file_name' => $fileEntity->getFileName(),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('delete笔记文件fail', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * delete流式识别文件（总结完成后清理）.
     *
     * @param string $fileId 文件ID
     * @return bool 是否deletesuccess
     */
    public function deleteTranscriptFile(string $fileId): bool
    {
        try {
            $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
            if ($fileEntity === null) {
                $this->logger->warning('流式识别文件不存在', ['file_id' => $fileId]);
                return false;
            }

            $this->taskFileDomainService->deleteById($fileEntity->getFileId());

            $this->logger->info('delete流式识别文件success', [
                'file_id' => $fileId,
                'file_name' => $fileEntity->getFileName(),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('delete流式识别文件fail', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * create笔记文件（放在显示目录）.
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
        // ⚠️ 使用 CoContext 和 di() get正确的语言和翻译
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
            logPrefix: '预设笔记文件'
        );
    }

    /**
     * create流式识别文件（放在隐藏目录）.
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
        // ⚠️ 使用 CoContext 和 di() get正确的语言和翻译
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
            logPrefix: '预设流式识别文件'
        );
    }

    /**
     * create预设文件的通用method.
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

        // 元数据
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
            'file_size' => 0, // 初始为0，前端写入后会update
            'external_url' => '',
            'storage_type' => 'workspace',
            'is_hidden' => $isHidden,
            'is_directory' => false,
            'sort' => 0,
            'parent_id' => $parentId,
            'source' => 2, // 2-项目目录
            'metadata' => Json::encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->taskFileDomainService->insertOrIgnore($taskFileEntity);
        if ($result !== null) {
            return $result;
        }

        // 如果插入被忽略（文件已存在），query现有record
        $existingFile = $this->taskFileDomainService->getByProjectIdAndFileKey($projectId, $fileKey);
        if ($existingFile !== null) {
            $this->logger->info(sprintf('%s已存在，使用现有record', $logPrefix), [
                'task_key' => $taskKey,
                'file_id' => $existingFile->getFileId(),
            ]);
            return $existingFile;
        }

        ExceptionBuilder::throw(AsrErrorCode::CreatePresetFileFailed);
    }
}
