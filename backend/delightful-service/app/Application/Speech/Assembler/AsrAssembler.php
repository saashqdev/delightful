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
 * ASR 组装器
 * 负责 ASR 相关的实体组装和pathconvert.
 *
 * pathformatinstruction：
 * - 工作区相对path (workspace-relative): .asr_recordings/session_xxx 或 录音总结_xxx
 * - 项目工作directory (work directory): project_123/workspace
 * - organization码+APP_ID+bucket_md5前缀 (full prefix): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/
 * - 完整path/file_key (full path): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx
 */
class AsrAssembler
{
    /**
     * createdirectory实体.
     *
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @param int $projectId 项目ID
     * @param string $relativePath 相对path（如：.asr_recordings/task_123 或 录音总结_xxx）
     * @param string $fullPrefix 完整前缀（organization码+APP_ID+bucket_md5，如：DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/）
     * @param string $workDir 工作directory
     * @param int $rootDirectoryId 根directoryID
     * @param bool $isHidden 是否为隐藏directory
     * @param null|string $taskKey task键（仅隐藏directoryneed）
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

        // 确定file名：隐藏directoryuse basename，显示directoryuse完整path
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
     * @param string $fullPrefix organization码+APP_ID+bucket_md5前缀 (如: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/)
     * @param string $workDir 项目工作directory (如: project_123/workspace)
     * @param string $relativePath 工作区相对path (如: .asr_recordings/session_xxx)
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
     * 从 file_key 提取工作区相对path.
     *
     * 将完整的 file_key convert为工作区相对path，用于沙箱 API call和界面展示
     * convert关系: relativePath = extractWorkspaceRelativePath(file_key)
     *
     * @param string $fileKey 完整 file_key (如: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx)
     * @return string 工作区相对path (如: .asr_recordings/session_xxx)
     */
    public static function extractWorkspaceRelativePath(string $fileKey): string
    {
        // 标准化path分隔符
        $normalizedPath = str_replace('\\', '/', trim($fileKey, '/'));

        // 查找 workspace/ 的position
        $workspacePos = strpos($normalizedPath, '/workspace/');
        if ($workspacePos !== false) {
            // 提取 workspace/ 后面的部分
            $relativePath = substr($normalizedPath, $workspacePos + 11); // 11 = strlen('/workspace/')

            // 如果相对path不为空，return相对path
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // 如果没有找到 /workspace/，尝试查找 workspace/ 开头的情况
        if (str_starts_with($normalizedPath, 'workspace/')) {
            $relativePath = substr($normalizedPath, 10); // 移除 'workspace/' 前缀
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // 如果都没找到workspace标识，直接returnoriginalpath（可能已经是相对path）
        return $normalizedPath;
    }
}
