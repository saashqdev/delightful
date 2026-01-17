<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config;

/**
 * ASR streaming transcript file config
 * Streaming transcript file handling for the finishTask API.
 */
readonly class AsrTranscriptFileConfig
{
    /**
     * @param string $sourcePath Source file path (relative to workspace)
     * @param string $action Operation type (delete - remove directly)
     */
    public function __construct(
        private string $sourcePath,
        private string $action = 'delete'
    ) {
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
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
            'action' => $this->action,
        ];
    }
}
