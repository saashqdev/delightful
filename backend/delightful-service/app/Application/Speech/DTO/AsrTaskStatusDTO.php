<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Enum\AsrTaskStatusEnum;

/**
 * ASRtaskstatusDTO - 管理Redis Hashfieldmapping.
 * thisnotisfrom JSON response结构come，whileisuseat管理taskstatus
 */
class AsrTaskStatusDTO
{
    public string $taskKey = '';

    public string $userId = '';

    public ?string $organizationCode = null; // organizationencoding（useatfrom动总结）

    // analogous：project_821749697183776769/workspace/录音总结_20250910_174251/original录音file.webm
    public ?string $filePath = null; // work区filepath

    // fileID（databasemiddleactualID）
    public ?string $audioFileId = null; // audiofileID（writedelightful_super_agent_task_files表backreturnID）

    // note fileinfo
    public ?string $noteFileName = null; // notefile名（andaudiofilein同onedirectory，fornullindicateno笔记file）

    public ?string $noteFileId = null; // notefileID（useatchatmessagemiddlefilequote）

    // presetfileinfo（useatfront端write）
    public ?string $presetNoteFileId = null; // preset笔记fileID

    public ?string $presetTranscriptFileId = null; // presetstream识别fileID

    public ?string $presetNoteFilePath = null; // preset笔记file相topath

    public ?string $presetTranscriptFilePath = null; // presetstream识别file相topath

    // projectand话题info
    public ?string $projectId = null; // projectID

    public ?string $topicId = null; // 话题ID

    // 录音directoryinfo
    public ?string $tempHiddenDirectory = null; // hiddendirectorypath（存放minuteslicefile）

    public ?string $displayDirectory = null; // displaydirectorypath（存放streamtextand笔记）

    public ?int $tempHiddenDirectoryId = null; // hiddendirectoryfileID

    public ?int $displayDirectoryId = null; // displaydirectoryfileID

    public AsrTaskStatusEnum $status = AsrTaskStatusEnum::FAILED;

    // 录音status管理field
    public ?string $modelId = null; // AI modelID，useatfrom动总结

    public ?string $recordingStatus = null; // 录音status：start|recording|paused|stopped

    public bool $sandboxTaskCreated = false; // 沙箱taskwhetheralreadycreate

    public bool $isPaused = false; // whether处atpausestatus（useattimeout判断）

    public ?string $sandboxId = null; // 沙箱ID

    public int $sandboxRetryCount = 0; // 沙箱startretrycount

    public int $serverSummaryRetryCount = 0; // service端总结触hairretrycount

    public bool $serverSummaryLocked = false; // service端总结whetherlock定customer端

    // ASR contentand笔记（useatgeneratetitle）
    public ?string $asrStreamContent = null; // ASR stream识别content

    public ?string $noteContent = null; // 笔记content

    public ?string $noteFileType = null; // 笔记filetype（md、txt、json）

    public ?string $language = null; // 语type（zh_CN、en_USetc），useatgeneratetitleo clockuse

    public ?string $uploadGeneratedTitle = null; // upload-tokens generatetitle（useat summary 复use）

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

        // projectand话题info
        $this->projectId = self::getStringValue($data, ['project_id', 'projectId']);
        $this->topicId = self::getStringValue($data, ['topic_id', 'topicId']);

        // 录音directoryinfo（from动清洗for相topath）
        $this->tempHiddenDirectory = self::extractRelativePath(
            self::getStringValue($data, ['temp_hidden_directory', 'tempHiddenDirectory'])
        );
        $this->displayDirectory = self::extractRelativePath(
            self::getStringValue($data, ['display_directory', 'displayDirectory'])
        );
        $this->tempHiddenDirectoryId = self::getIntValue($data, ['temp_hidden_directory_id', 'tempHiddenDirectoryId']);
        $this->displayDirectoryId = self::getIntValue($data, ['display_directory_id', 'displayDirectoryId']);

        // 录音status管理field
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

        // ASR contentand笔记
        $this->asrStreamContent = self::getStringValue($data, ['asr_stream_content', 'asrStreamContent']);
        $this->noteContent = self::getStringValue($data, ['note_content', 'noteContent']);
        $this->noteFileType = self::getStringValue($data, ['note_file_type', 'noteFileType']);
        $this->language = $data['language'] ?? null;
        $this->uploadGeneratedTitle = self::getStringValue($data, ['upload_generated_title', 'uploadGeneratedTitle']);
    }

    /**
     * fromarraycreateDTOobject
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * convertforarray（useatstoragetoRedis）.
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
     * checkwhetherfornull（not存in）.
     */
    public function isEmpty(): bool
    {
        return empty($this->taskKey) && empty($this->userId);
    }

    /**
     * updatestatus
     */
    public function updateStatus(AsrTaskStatusEnum $status): void
    {
        $this->status = $status;
    }

    /**
     * check总结whetheralreadycomplete（poweretcproperty判断）.
     * 判断standard：audiofilealreadymerge（audioFileId 存in）and录音alreadystop.
     */
    public function isSummaryCompleted(): bool
    {
        return ! empty($this->audioFileId)
            && $this->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
            && $this->status === AsrTaskStatusEnum::COMPLETED;
    }

    /**
     * 判断service端总结whethertocustomer端addlock.
     */
    public function hasServerSummaryLock(): bool
    {
        return $this->serverSummaryLocked && ! $this->isSummaryCompleted();
    }

    /**
     * recordonetimeservice端总结尝试.
     */
    public function markServerSummaryAttempt(): void
    {
        ++$this->serverSummaryRetryCount;
        $this->serverSummaryLocked = true;
    }

    /**
     * inonetimeservice端总结endbackupdatestatus.
     */
    public function finishServerSummaryAttempt(bool $success): void
    {
        if ($success) {
            $this->serverSummaryRetryCount = 0;
            $this->serverSummaryLocked = false;
        }
    }

    /**
     * extract相toat workspace 相topath
     * ifpathcontain workspace/，extractitsback部minute
     * this样canfrom动修just Redis middlestorage旧formatdata（completepath）.
     *
     * @param null|string $path originalpath
     * @return null|string 相topath
     */
    private static function extractRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        // ifpathcontain workspace/，extract workspace/ backsurface部minute
        if (str_contains($path, 'workspace/')) {
            $parts = explode('workspace/', $path, 2);
            return $parts[1] ?? $path;
        }

        return $path;
    }

    /**
     * fromarraymiddle按优先levelgetstringvalue（support snake_case and camelCase）.
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys key名column表（按优先levelsort）
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
     * fromarraymiddle按优先levelgetintegervalue（support snake_case and camelCase）.
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys key名column表（按优先levelsort）
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
     * fromarraymiddle按优先levelgetbooleanvalue（support多typeformat：true/false、1/0、'1'/'0'）.
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys key名column表（按优先levelsort）
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

            // handlestring '1' or '0'
            if ($value === '1' || $value === 1) {
                return true;
            }

            if ($value === '0' || $value === 0) {
                return false;
            }

            // othervalue按truevalue判断
            return (bool) $value;
        }

        return false;
    }
}
