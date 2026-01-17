<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config;

/**
 * ASR note file config
 * Note file handling config for the finishTask API.
 */
readonly class AsrNoteFileConfig
{
    /**
     * @param string $sourcePath Source file path (relative to workspace)
     * @param string $targetPath Target file path (relative to workspace)
     * @param string $action Operation type (rename_and_move)
     */
    public function __construct(
        private string $sourcePath,
        private string $targetPath,
        private string $action = 'rename_and_move'
    ) {
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Convert to array (for HTTP request).
     */
    public function toArray(): array
    {
        return [
            'source_path' => $this->sourcePath,
            'target_path' => $this->targetPath,
            'action' => $this->action,
        ];
    }
}
