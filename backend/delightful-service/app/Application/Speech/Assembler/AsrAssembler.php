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
 * ASR grouploader
 * responsible ASR 相closeactualbodygroup装andpathconvert.
 *
 * pathformatinstruction:
 * - workregiontopath (workspace-relative): .asr_recordings/session_xxx or recordingsummary_xxx
 * - projectworkdirectory (work directory): project_123/workspace
 * - organizationcode+APP_ID+bucket_md5frontsuffix (full prefix): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/
 * - completepath/file_key (full path): DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx
 */
class AsrAssembler
{
    /**
     * createdirectoryactualbody.
     *
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @param int $projectId projectID
     * @param string $relativePath 相topath(like:.asr_recordings/task_123 or recordingsummary_xxx)
     * @param string $fullPrefix completefrontsuffix(organizationcode+APP_ID+bucket_md5,like:DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/)
     * @param string $workDir workdirectory
     * @param int $rootDirectoryId rootdirectoryID
     * @param bool $isHidden whetherforhiddendirectory
     * @param null|string $taskKey taskkey(onlyhiddendirectoryneed)
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

        // buildcomplete file_key
        $fileKey = WorkDirectoryUtil::getFullFileKey($fullPrefix, $workDir, $relativePath);
        $fileKey = rtrim($fileKey, '/') . '/';

        // certainfilename:hiddendirectoryuse basename,displaydirectoryusecompletepath
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
     * buildcomplete file_key.
     *
     * convertclose系: file_key = fullPrefix + workDir + "/" + relativePath
     *
     * @param string $fullPrefix organizationcode+APP_ID+bucket_md5frontsuffix (like: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/)
     * @param string $workDir projectworkdirectory (like: project_123/workspace)
     * @param string $relativePath workregiontopath (like: .asr_recordings/session_xxx)
     * @return string complete file_key (like: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx)
     */
    public static function buildFileKey(
        string $fullPrefix,
        string $workDir,
        string $relativePath
    ): string {
        return WorkDirectoryUtil::getFullFileKey($fullPrefix, $workDir, $relativePath);
    }

    /**
     * from file_key extractworkregiontopath.
     *
     * willcomplete file_key convertforworkregiontopath,useatsandbox API calland界surfaceshow
     * convertclose系: relativePath = extractWorkspaceRelativePath(file_key)
     *
     * @param string $fileKey complete file_key (like: DT001/open/5f4dcc3b5aa765d61d8327deb882cf99/project_123/workspace/.asr_recordings/session_xxx)
     * @return string workregiontopath (like: .asr_recordings/session_xxx)
     */
    public static function extractWorkspaceRelativePath(string $fileKey): string
    {
        // standard化pathminuteseparator
        $normalizedPath = str_replace('\\', '/', trim($fileKey, '/'));

        // find workspace/ position
        $workspacePos = strpos($normalizedPath, '/workspace/');
        if ($workspacePos !== false) {
            // extract workspace/ backsurface部minute
            $relativePath = substr($normalizedPath, $workspacePos + 11); // 11 = strlen('/workspace/')

            // if相topathnotforempty,return相topath
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // ifnothavefindto /workspace/,tryfind workspace/ openheadsituation
        if (str_starts_with($normalizedPath, 'workspace/')) {
            $relativePath = substr($normalizedPath, 10); // moveexcept 'workspace/' frontsuffix
            if (! empty($relativePath)) {
                return $relativePath;
            }
        }

        // ifallnotfindtoworkspaceidentifier,directlyreturnoriginalpath(maybealreadyalreadyis相topath)
        return $normalizedPath;
    }
}
