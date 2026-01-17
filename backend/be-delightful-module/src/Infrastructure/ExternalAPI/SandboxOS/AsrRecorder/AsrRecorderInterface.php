<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response\AsrRecorderResponse;

/**
 * ASR recording service interface.
 */
interface AsrRecorderInterface
{
    /**
     * Start an ASR recording task.
     * Corresponds to sandbox POST /api/asr/task/start.
     *
     * @param string $sandboxId Sandbox ID
     * @param string $taskKey Task key
     * @param string $sourceDir Audio shard directory (relative path)
     * @param string $workspaceDir Workspace directory, defaults to .workspace
     * @param null|AsrNoteFileConfig $noteFileConfig Note file config (optional)
     * @param null|AsrTranscriptFileConfig $transcriptFileConfig Streaming transcript config (optional)
     * @return AsrRecorderResponse Response result
     */
    public function startTask(
        string $sandboxId,
        string $taskKey,
        string $sourceDir,
        string $workspaceDir = '.workspace',
        ?AsrNoteFileConfig $noteFileConfig = null,
        ?AsrTranscriptFileConfig $transcriptFileConfig = null
    ): AsrRecorderResponse;

    /**
     * Finish an ASR recording task and merge (V2 structured version).
     * Corresponds to sandbox POST /api/asr/task/finish.
     * Supports polling status (call multiple times with same params).
     *
     * @param string $sandboxId Sandbox ID
     * @param string $taskKey Task key
     * @param string $workspaceDir Workspace directory
     * @param AsrAudioConfig $audioConfig Audio config
     * @param null|AsrNoteFileConfig $noteFileConfig Note file config
     * @param null|AsrTranscriptFileConfig $transcriptFileConfig Streaming transcript config
     * @return AsrRecorderResponse Response result
     */
    public function finishTask(
        string $sandboxId,
        string $taskKey,
        string $workspaceDir,
        AsrAudioConfig $audioConfig,
        ?AsrNoteFileConfig $noteFileConfig = null,
        ?AsrTranscriptFileConfig $transcriptFileConfig = null
    ): AsrRecorderResponse;
    /**
     * Cancel an ASR recording task.
     * Corresponds to sandbox POST /api/asr/task/cancel.
     *
     * @param string $sandboxId Sandbox ID
     * @param string $taskKey Task key
     * @param string $workspaceDir Workspace directory, defaults to .workspace
     * @return AsrRecorderResponse Response result
     */
    public function cancelTask(
        string $sandboxId,
        string $taskKey,
        string $workspaceDir = '.workspace'
    ): AsrRecorderResponse;
}
