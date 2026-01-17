<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config;

/**
 * ASR audio config
 * Audio merge configuration for the finishTask API.
 */
readonly class AsrAudioConfig
{
    /**
     * @param string $sourceDir Audio shard source directory (relative to workspace)
     * @param string $targetDir Target directory (relative to workspace)
     * @param string $outputFilename Output filename (without extension)
     */
    public function __construct(
        private string $sourceDir,
        private string $targetDir,
        private string $outputFilename
    ) {
    }

    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    public function getTargetDir(): string
    {
        return $this->targetDir;
    }

    public function getOutputFilename(): string
    {
        return $this->outputFilename;
    }

    /**
     * Convert to array (for HTTP request).
     */
    public function toArray(): array
    {
        return [
            'source_dir' => $this->sourceDir,
            'target_dir' => $this->targetDir,
            'output_filename' => $this->outputFilename,
        ];
    }
}
