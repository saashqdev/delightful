<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR recording type enum.
 *
 * Scope: API parameter - /api/v1/asr/upload-tokens
 * Purpose: Distinguish recording source types to decide whether to create preset files
 * Usage:
 * - Frontend recording: real-time recording that needs preset notes and streaming ASR files for live writes
 * - File upload: user uploads a complete recording file; preset files are not needed
 */
enum AsrRecordingTypeEnum: string
{
    case FRONTEND_RECORDING = 'frontend_recording';  // Frontend recording
    case FILE_UPLOAD = 'file_upload';                // Directly uploaded recording file

    /**
    * Get type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::FRONTEND_RECORDING => 'Frontend recording',
            self::FILE_UPLOAD => 'File upload',
        };
    }

    /**
    * Whether preset files are needed.
     */
    public function needsPresetFiles(): bool
    {
        return match ($this) {
            self::FRONTEND_RECORDING => true,   // Frontend recording needs preset files for live writes
            self::FILE_UPLOAD => false,         // File upload does not need preset files (recording already exists)
        };
    }

    /**
    * Safely create the enum from a string.
     */
    public static function fromString(string $type): ?self
    {
        return self::tryFrom($type);
    }

    /**
     * Get the default type.
     */
    public static function default(): self
    {
        return self::FILE_UPLOAD;  // Default to file upload (backward compatible)
    }
}
