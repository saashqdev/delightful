<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\FileType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskFileSource;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Codec\Json;

/**
 * ASR group装器
 * 负责 ASR 相关的实bodygroup装和pathconvert.
 *
 * pathformatinstruction：
 * - work区相对path (workspace-relative): .asr_recordings/session_xxx or 录音总结_xxx
 * - projectworkdirectory (work directory): project_123/workspace
 * - organization码+APP_ID+bucket_md5front缀 (full prefix): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/
 * - 完整path/file_key (full path): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx
 */
class AsrAssembler
{
    /**
     * createdirectory实body.
     *
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @param int $projectId projectID
     * @param string $relativePath 相对path（如：.asr_recordings/task_123 or 录音总结_xxx）
     * @param string $fullPrefix 完整front缀（organization码+APP_ID+bucket_md5，如：DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/）
     * @param string $workDir workdirectory
     * @param int $rootDirectoryId rootdirectoryID
     * @param bool $isHidden whether为hiddendirectory
     * @param null|string $taskKey task键（仅hiddendirectoryneed）
     */
    public static function createDirectoryEntity(
        string $userId,
        string $organizationCode,
        int $projectId,
        string $relativePath,
        string $fullPrefix,
        string $workDir,
        int $rootDirectoryId,
        bool $isHidden = false,
        ?string $taskKey = null
    ): TaskFileEntity {
        // build metadata
        $metadata = [
            'created_by' => 'asr_prepare_recording',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($isHidden) {
            $metadata['asr_temp_directory'] = true;
            if ($taskKey !== null) {
                $metadata['task_key'] = $taskKey;
            }
        } else {
            $metadata['asr_display_directory'] = true;
        }

        // build完整的 file_key
        $fileKey = WorkDirectoryUtil::getFullFileKey($fullPrefix, $workDir, $relativePath);
        $fileKey = rtrim($fileKey, '/') . '/';

        // 确定file名：hiddendirectoryuse basename，displaydirectoryuse完整path
        $fileName = $isHidden ? basename($relativePath) : $relativePath;

        return new TaskFileEntity([
            'user_id' => $userId,
            'organization_code' => $organizationCode,
            'project_id' => $projectId,
            'topic_id' => 0,
            'task_id' => 0,
            'file_type' => FileType::DIRECTORY->value,
            'file_name' => $fileName,
            'file_extension' => '',
            'file_key' => $fileKey,
            'file_size' => 0,
            'external_url' => '',
            'storage_type' => StorageType::WORKSPACE->value,
            'is_hidden' => $isHidden,
            'is_directory' => true,
            'sort' => 0,
            'parent_id' => $rootDirectoryId,
            'source' => TaskFileSource::PROJECT_DIRECTORY->value,
            'metadata' => Json::encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * build完整 file_key.
     *
     * convert关系: file_key = fullPrefix + workDir + "/" + relativePath
     *
     * @param string $fullPrefix organization码+APP_ID+bucket_md5front缀 (如: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/)
     * @param string $workDir projectworkdirectory (如: project_123/workspace)
     * @param string $relativePath work区相对path (如: .asr_recordings/session_xxx)
     * @return string 完整 file_key (如: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx)
     */
    public static function buildFileKey(
        string $fullPrefix,
        string $workDir,
        string $relativePath
    ): string {
        return WorkDirectoryUtil::getFullFileKey($fullPrefix, $workDir, $relativePath);
    }

    /**
     * from file_key extractwork区相对path.
     *
     * 将完整的 file_key convert为work区相对path，useat沙箱 API call和界surfaceshow
     * convert关系: relativePath = extractWorkspaceRelativePath(file_key)
     *
     * @param string $fileKey 完整 file_key (如: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx)
     * @return string work区相对path (如: .asr_recordings/session_xxx)
     */
    public static function extractWorkspaceRelativePath(string $fileKey): string
    {
        // standard化pathminute隔符
        $normalizedPath = str_replace('\\', '/', trim($fileKey, '/'));

        // find workspace/ 的position
        $workspacePos = strpos($normalizedPath, '/workspace/');
        if ($workspacePos !== false) {
            // extract workspace/ backsurface的部minute
            $relativePath = substr($normalizedPath, $workspacePos + 11); // 11 = strlen('/workspace/')

            // if相对pathnot为空，return相对path
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // ifnothave找to /workspace/，尝试find workspace/ 开head的情况
        if (str_starts_with($normalizedPath, 'workspace/')) {
            $relativePath = substr($normalizedPath, 10); // 移except 'workspace/' front缀
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // ifallnot找toworkspace标识，直接returnoriginalpath（可能已经是相对path）
        return $normalizedPath;
    }
}
