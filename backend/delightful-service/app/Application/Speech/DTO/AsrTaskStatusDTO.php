<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Enum\AsrTaskStatusEnum;

/**
 * ASRtaskstatusDTO - manageRedis Hashfieldmapping.
 * thisnotisfrom JSON responsestructurecome,whileisuseatmanagetaskstatus
 */
class AsrTaskStatusDTO
{
    public string $taskKey = '';

    public string $userId = '';

    public ?string $organizationCode = null; // organizationencoding(useatfromautosummary)

    // analogous:project_821749697183776769/workspace/recordingsummary_20250910_174251/originalrecordingfile.webm
    public ?string $filePath = null; // workregionfilepath

    // fileID(databasemiddleactualID)
    public ?string $audioFileId = null; // audiofileID(writedelightful_super_agent_task_filestablebackreturnID)

    // note fileinfo
    public ?string $noteFileName = null; // notefilename(andaudiofileinsameonedirectory,fornullindicatenonotefile)

    public ?string $noteFileId = null; // notefileID(useatchatmessagemiddlefilequote)

    // presetfileinfo(useatfrontclientwrite)
    public ?string $presetNoteFileId = null; // presetnotefileID

    public ?string $presetTranscriptFileId = null; // presetstreamidentifyfileID

    public ?string $presetNoteFilePath = null; // presetnotefilerelatedtopath

    public ?string $presetTranscriptFilePath = null; // presetstreamidentifyfilerelatedtopath

    // projectandtopicinfo
    public ?string $projectId = null; // projectID

    public ?string $topicId = null; // topicID

    // recordingdirectoryinfo
    public ?string $tempHiddenDirectory = null; // hiddendirectorypath(storeminuteslicefile)

    public ?string $displayDirectory = null; // displaydirectorypath(storestreamtextandnote)

    public ?int $tempHiddenDirectoryId = null; // hiddendirectoryfileID

    public ?int $displayDirectoryId = null; // displaydirectoryfileID

    public AsrTaskStatusEnum $status = AsrTaskStatusEnum::FAILED;

    // recordingstatusmanagefield
    public ?string $modelId = null; // AI modelID,useatfromautosummary

    public ?string $recordingStatus = null; // recordingstatus:start|recording|paused|stopped

    public bool $sandboxTaskCreated = false; // sandboxtaskwhetheralreadycreate

    public bool $isPaused = false; // whether处atpausestatus(useattimeoutjudge)

    public ?string $sandboxId = null; // sandboxID

    public int $sandboxRetryCount = 0; // sandboxstartretrycount

    public int $serverSummaryRetryCount = 0; // serviceclientsummarytouchhairretrycount

    public bool $serverSummaryLocked = false; // serviceclientsummarywhetherlocksetcustomerclient

    // ASR contentandnote(useatgeneratetitle)
    public ?string $asrStreamContent = null; // ASR streamidentifycontent

    public ?string $noteContent = null; // notecontent

    public ?string $noteFileType = null; // notefiletype(md,txt,json)

    public ?string $language = null; // languagetype(zh_CN,en_USetc),useatgeneratetitleo clockuse

    public ?string $uploadGeneratedTitle = null; // upload-tokens generatetitle(useat summary 复use)

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

        // projectandtopicinfo
        $this->projectId = self::getStringValue($data, ['project_id', 'projectId']);
        $this->topicId = self::getStringValue($data, ['topic_id', 'topicId']);

        // recordingdirectoryinfo(fromautocleanforrelatedtopath)
        $this->tempHiddenDirectory = self::extractRelativePath(
            self::getStringValue($data, ['temp_hidden_directory', 'tempHiddenDirectory'])
        );
        $this->displayDirectory = self::extractRelativePath(
            self::getStringValue($data, ['display_directory', 'displayDirectory'])
        );
        $this->tempHiddenDirectoryId = self::getIntValue($data, ['temp_hidden_directory_id', 'tempHiddenDirectoryId']);
        $this->displayDirectoryId = self::getIntValue($data, ['display_directory_id', 'displayDirectoryId']);

        // recordingstatusmanagefield
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

        // ASR contentandnote
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
     * convertforarray(useatstoragetoRedis).
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
     * checkwhetherfornull(notexistsin).
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
     * checksummarywhetheralreadycomplete(poweretcpropertyjudge).
     * judgestandard:audiofilealreadymerge(audioFileId existsin)andrecordingalreadystop.
     */
    public function isSummaryCompleted(): bool
    {
        return ! empty($this->audioFileId)
            && $this->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
            && $this->status === AsrTaskStatusEnum::COMPLETED;
    }

    /**
     * judgeserviceclientsummarywhethertocustomerclientaddlock.
     */
    public function hasServerSummaryLock(): bool
    {
        return $this->serverSummaryLocked && ! $this->isSummaryCompleted();
    }

    /**
     * recordonetimeserviceclientsummarytry.
     */
    public function markServerSummaryAttempt(): void
    {
        ++$this->serverSummaryRetryCount;
        $this->serverSummaryLocked = true;
    }

    /**
     * inonetimeserviceclientsummaryendbackupdatestatus.
     */
    public function finishServerSummaryAttempt(bool $success): void
    {
        if ($success) {
            $this->serverSummaryRetryCount = 0;
            $this->serverSummaryLocked = false;
        }
    }

    /**
     * extractrelatedtoat workspace relatedtopath
     * ifpathcontain workspace/,extractitsbackdepartmentminute
     * this样canfromauto modifyjust Redis middlestorageoldformatdata(completepath).
     *
     * @param null|string $path originalpath
     * @return null|string relatedtopath
     */
    private static function extractRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        // ifpathcontain workspace/,extract workspace/ backsurfacedepartmentminute
        if (str_contains($path, 'workspace/')) {
            $parts = explode('workspace/', $path, 2);
            return $parts[1] ?? $path;
        }

        return $path;
    }

    /**
     * fromarraymiddlebyprioritylevelgetstringvalue(support snake_case and camelCase).
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys keynamecolumntable(byprioritylevelsort)
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
     * fromarraymiddlebyprioritylevelgetintegervalue(support snake_case and camelCase).
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys keynamecolumntable(byprioritylevelsort)
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
     * fromarraymiddlebyprioritylevelgetbooleanvalue(supportmultipletypeformat:true/false,1/0,'1'/'0').
     *
     * @param array<string, mixed> $data dataarray
     * @param array<string> $keys keynamecolumntable(byprioritylevelsort)
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

            // othervaluebytruevaluejudge
            return (bool) $value;
        }

        return false;
    }
}
