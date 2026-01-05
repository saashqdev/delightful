<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR recording status enum (frontend interaction protocol).
 *
 * Scope: frontend interaction layer - client recording status reporting
 * Purpose: represent the real-time recording status on the frontend
 * Usage scenarios:
 * - Frontend recording status report API (status parameter)
 * - Recording heartbeat keep-alive
 * - Control start/pause/resume/stop of recording
 *
 * Differences from other enums:
 * - AsrRecordingStatusEnum: frontend real-time recording status (interaction layer) ✓ this enum
 * - AsrTaskStatusEnum: internal task lifecycle status (business layer)
 * - SandboxAsrStatusEnum: sandbox merge task status (infrastructure layer)
 *
 * State transitions: start → recording ⇄ paused → stopped
 * Note: these states are defined by the frontend and are independent from backend internal states
 */
enum AsrRecordingStatusEnum: string
{
    case START = 'start';         // Start recording: user first clicks the record button
    case RECORDING = 'recording'; // Recording (heartbeat): frontend continuously reports to keep the session alive
    case PAUSED = 'paused';       // Paused: user pauses recording and can resume
    case STOPPED = 'stopped';     // Stopped: user stops recording, triggering audio merge
    case CANCELED = 'canceled';   // Canceled: user cancels recording, stops the task, and cleans up data

    /**
     * Validate whether the status value is valid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, ['start', 'recording', 'paused', 'stopped', 'canceled'], true);
    }

    /**
     * Create an enum from a string.
     */
    public static function tryFromString(string $status): ?self
    {
        return match ($status) {
            'start' => self::START,
            'recording' => self::RECORDING,
            'paused' => self::PAUSED,
            'stopped' => self::STOPPED,
            'canceled' => self::CANCELED,
            default => null,
        };
    }
}
