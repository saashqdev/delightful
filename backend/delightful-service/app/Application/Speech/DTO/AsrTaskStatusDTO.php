<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Enum\AsrTaskStatusEnum;

/**
 * ASRtaskstatusDTO - 管理Redis Hash字段映射.
 * 这不是从 JSON 响应结构来的，而是用于管理taskstatus
 */
class AsrTaskStatusDTO
{
    public string $taskKey = '';

    public string $userId = '';

    public ?string $organizationCode = null; // organization编码（用于自动总结）

    // 类似：project_821749697183776769/workspace/录音总结_20250910_174251/original录音file.webm
    public ?string $filePath = null; // 工作区file路径

    // fileID（数据库中的actualID）
    public ?string $audioFileId = null; // audiofileID（writedelightful_super_agent_task_files表后return的ID）

    // note fileinfo
    public ?string $noteFileName = null; // notefile名（与audiofile在同一目录，为null表示无笔记file）

    public ?string $noteFileId = null; // notefileID（用于chatmessage中的filequote）

    // presetfileinfo（用于前端write）
    public ?string $presetNoteFileId = null; // preset笔记fileID

    public ?string $presetTranscriptFileId = null; // presetstream识别fileID

    public ?string $presetNoteFilePath = null; // preset笔记file相对路径

    public ?string $presetTranscriptFilePath = null; // presetstream识别file相对路径

    // 项目和话题info
    public ?string $projectId = null; // 项目ID

    public ?string $topicId = null; // 话题ID

    // 录音目录info
    public ?string $tempHiddenDirectory = null; // 隐藏目录路径（存放分片file）

    public ?string $displayDirectory = null; // 显示目录路径（存放stream文本和笔记）

    public ?int $tempHiddenDirectoryId = null; // 隐藏目录的fileID

    public ?int $displayDirectoryId = null; // 显示目录的fileID

    public AsrTaskStatusEnum $status = AsrTaskStatusEnum::FAILED;

    // 录音status管理字段
    public ?string $modelId = null; // AI 模型ID，用于自动总结

    public ?string $recordingStatus = null; // 录音status：start|recording|paused|stopped

    public bool $sandboxTaskCreated = false; // 沙箱task是否已create

    public bool $isPaused = false; // 是否处于暂停status（用于超时判断）

    public ?string $sandboxId = null; // 沙箱ID

    public int $sandboxRetryCount = 0; // 沙箱启动重试次数

    public int $serverSummaryRetryCount = 0; // 服务端总结触发重试次数

    public bool $serverSummaryLocked = false; // 服务端总结是否锁定客户端

    // ASR 内容和笔记（用于generate标题）
    public ?string $asrStreamContent = null; // ASR stream识别内容

    public ?string $noteContent = null; // 笔记内容

    public ?string $noteFileType = null; // 笔记filetype（md、txt、json）

    public ?string $language = null; // 语种（zh_CN、en_US等），用于generate标题时use

    public ?string $uploadGeneratedTitle = null; // upload-tokens generate的标题（用于 summary 复用）

    public function __construct(array $data = [])
    {
        $this->taskKey = self::getStringValue($data, ['task_key', 'taskKey'], '');
        $this->userId = self::getStringValue($data, ['user_id', 'userId'], '');
        $this->organizationCode = self::getStringValue($data, ['organization_code', 'organizationCode']);

        $this->status = AsrTaskStatusEnum::fromString($data['status'] ?? 'failed');
        $this->filePath = self::getStringValue($data, ['file_path', 'filePath', 'file_name', 'fileName']);
        $this->audioFileId = self::getStringValue($data, ['audio_file_id', 'audioFileId']);
        $this->noteFileName = self::getStringValue($data, ['note_file_name', 'noteFileName']);
        $this->noteFileId = self::getStringValue($data, ['note_file_id', 'noteFileId']);

        // 项目和话题info
        $this->projectId = self::getStringValue($data, ['project_id', 'projectId']);
        $this->topicId = self::getStringValue($data, ['topic_id', 'topicId']);

        // 录音目录info（自动清洗为相对路径）
        $this->tempHiddenDirectory = self::extractRelativePath(
            self::getStringValue($data, ['temp_hidden_directory', 'tempHiddenDirectory'])
        );
        $this->displayDirectory = self::extractRelativePath(
            self::getStringValue($data, ['display_directory', 'displayDirectory'])
        );
        $this->tempHiddenDirectoryId = self::getIntValue($data, ['temp_hidden_directory_id', 'tempHiddenDirectoryId']);
        $this->displayDirectoryId = self::getIntValue($data, ['display_directory_id', 'displayDirectoryId']);

        // 录音status管理字段
        $this->modelId = self::getStringValue($data, ['model_id', 'modelId']);
        $this->recordingStatus = self::getStringValue($data, ['recording_status', 'recordingStatus']);
        $this->sandboxTaskCreated = self::getBoolValue($data, ['sandbox_task_created', 'sandboxTaskCreated']);
        $this->isPaused = self::getBoolValue($data, ['is_paused', 'isPaused']);
        $this->sandboxId = self::getStringValue($data, ['sandbox_id', 'sandboxId']);
        $this->sandboxRetryCount = self::getIntValue($data, ['sandbox_retry_count', 'sandboxRetryCount'], 0);
        $this->serverSummaryRetryCount = self::getIntValue($data, ['server_summary_retry_count', 'serverSummaryRetryCount'], 0);
        $this->serverSummaryLocked = self::getBoolValue($data, ['server_summary_locked', 'serverSummaryLocked']);

        // presetfileinfo
        $this->presetNoteFileId = self::getStringValue($data, ['preset_note_file_id', 'presetNoteFileId']);
        $this->presetTranscriptFileId = self::getStringValue($data, ['preset_transcript_file_id', 'presetTranscriptFileId']);
        $this->presetNoteFilePath = self::getStringValue($data, ['preset_note_file_path', 'presetNoteFilePath']);
        $this->presetTranscriptFilePath = self::getStringValue($data, ['preset_transcript_file_path', 'presetTranscriptFilePath']);

        // ASR 内容和笔记
        $this->asrStreamContent = self::getStringValue($data, ['asr_stream_content', 'asrStreamContent']);
        $this->noteContent = self::getStringValue($data, ['note_content', 'noteContent']);
        $this->noteFileType = self::getStringValue($data, ['note_file_type', 'noteFileType']);
        $this->language = $data['language'] ?? null;
        $this->uploadGeneratedTitle = self::getStringValue($data, ['upload_generated_title', 'uploadGeneratedTitle']);
    }

    /**
     * 从arraycreateDTOobject
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * convert为array（用于存储到Redis）.
     *
     * @return array<string, null|bool|int|string>
     */
    public function toArray(): array
    {
        return [
            'task_key' => $this->taskKey,
            'user_id' => $this->userId,
            'organization_code' => $this->organizationCode,
            'status' => $this->status->value,
            'file_path' => $this->filePath,
            'audio_file_id' => $this->audioFileId,
            'note_file_name' => $this->noteFileName,
            'note_file_id' => $this->noteFileId,
            'project_id' => $this->projectId,
            'topic_id' => $this->topicId,
            'temp_hidden_directory' => $this->tempHiddenDirectory,
            'display_directory' => $this->displayDirectory,
            'temp_hidden_directory_id' => $this->tempHiddenDirectoryId,
            'display_directory_id' => $this->displayDirectoryId,
            'model_id' => $this->modelId,
            'recording_status' => $this->recordingStatus,
            'sandbox_task_created' => $this->sandboxTaskCreated,
            'is_paused' => $this->isPaused,
            'sandbox_id' => $this->sandboxId,
            'sandbox_retry_count' => $this->sandboxRetryCount,
            'server_summary_retry_count' => $this->serverSummaryRetryCount,
            'server_summary_locked' => $this->serverSummaryLocked,
            'preset_note_file_id' => $this->presetNoteFileId,
            'preset_transcript_file_id' => $this->presetTranscriptFileId,
            'preset_note_file_path' => $this->presetNoteFilePath,
            'preset_transcript_file_path' => $this->presetTranscriptFilePath,
            'asr_stream_content' => $this->asrStreamContent,
            'note_content' => $this->noteContent,
            'note_file_type' => $this->noteFileType,
            'language' => $this->language,
            'upload_generated_title' => $this->uploadGeneratedTitle,
        ];
    }

    /**
     * check是否为null（不存在）.
     */
    public function isEmpty(): bool
    {
        return empty($this->taskKey) && empty($this->userId);
    }

    /**
     * 更新status
     */
    public function updateStatus(AsrTaskStatusEnum $status): void
    {
        $this->status = $status;
    }

    /**
     * check总结是否已complete（幂等性判断）.
     * 判断标准：audiofile已merge（audioFileId 存在）且录音已停止.
     */
    public function isSummaryCompleted(): bool
    {
        return ! empty($this->audioFileId)
            && $this->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
            && $this->status === AsrTaskStatusEnum::COMPLETED;
    }

    /**
     * 判断服务端总结是否对客户端加锁.
     */
    public function hasServerSummaryLock(): bool
    {
        return $this->serverSummaryLocked && ! $this->isSummaryCompleted();
    }

    /**
     * 记录一次服务端总结尝试.
     */
    public function markServerSummaryAttempt(): void
    {
        ++$this->serverSummaryRetryCount;
        $this->serverSummaryLocked = true;
    }

    /**
     * 在一次服务端总结end后更新status.
     */
    public function finishServerSummaryAttempt(bool $success): void
    {
        if ($success) {
            $this->serverSummaryRetryCount = 0;
            $this->serverSummaryLocked = false;
        }
    }

    /**
     * 提取相对于 workspace 的相对路径
     * 如果路径contain workspace/，提取其后的部分
     * 这样can自动修正 Redis 中存储的旧格式数据（完整路径）.
     *
     * @param null|string $path original路径
     * @return null|string 相对路径
     */
    private static function extractRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        // 如果路径contain workspace/，提取 workspace/ 后面的部分
        if (str_contains($path, 'workspace/')) {
            $parts = explode('workspace/', $path, 2);
            return $parts[1] ?? $path;
        }

        return $path;
    }

    /**
     * 从array中按优先级获取stringvalue（支持 snake_case 和 camelCase）.
     *
     * @param array<string, mixed> $data 数据array
     * @param array<string> $keys 键名列表（按优先级sort）
     * @param null|string $default defaultvalue
     */
    private static function getStringValue(array $data, array $keys, ?string $default = null): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return (string) $data[$key];
            }
        }
        return $default;
    }

    /**
     * 从array中按优先级获取整数value（支持 snake_case 和 camelCase）.
     *
     * @param array<string, mixed> $data 数据array
     * @param array<string> $keys 键名列表（按优先级sort）
     * @param null|int $default defaultvalue
     */
    private static function getIntValue(array $data, array $keys, ?int $default = null): ?int
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return (int) $data[$key];
            }
        }
        return $default;
    }

    /**
     * 从array中按优先级获取booleanvalue（支持多种格式：true/false、1/0、'1'/'0'）.
     *
     * @param array<string, mixed> $data 数据array
     * @param array<string> $keys 键名列表（按优先级sort）
     */
    private static function getBoolValue(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! isset($data[$key])) {
                continue;
            }

            $value = $data[$key];

            // handlebooleantype
            if (is_bool($value)) {
                return $value;
            }

            // handlestring '1' 或 '0'
            if ($value === '1' || $value === 1) {
                return true;
            }

            if ($value === '0' || $value === 0) {
                return false;
            }

            // 其他value按真value判断
            return (bool) $value;
        }

        return false;
    }
}
