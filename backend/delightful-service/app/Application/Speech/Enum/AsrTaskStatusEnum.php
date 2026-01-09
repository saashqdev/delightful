<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR taskstatusenum(inside部businessprocess).
 *
 * 【asuse域】inside部system - delightful-service businesslayer
 * 【use途】table示 ASR recordingsummarytaskalllifeperiodstatus
 * 【usescenario】
 * - taskstatuspersistence(Redis/database)
 * - businessprocesscontrolandpoweretcpropertyjudge
 * - 整bodytaskstatustrace(recording → merge → generatetitle → sendmessage)
 *
 * 【andotherenumdifference】
 * - AsrRecordingStatusEnum: front端recording实o clockstatus(recordinginteractionlayer)
 * - AsrTaskStatusEnum: inside部taskallprocessstatus(businessmanagelayer)✓ current
 * - SandboxAsrStatusEnum: sandboxmergetaskstatus(infrastructurelayer)
 *
 * 【statusstream转】created → processing → completed | failed
 */
enum AsrTaskStatusEnum: string
{
    case CREATED = 'created';              // alreadycreate:taskinitializecomplete,etc待process
    case PROCESSING = 'processing';        // processmiddle:justinexecuterecording,mergeorsummary
    case COMPLETED = 'completed';          // alreadycomplete:整 ASR processall部complete(includemessagesend)
    case FAILED = 'failed';                // fail:taskexecutefail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CREATED => 'alreadycreate',
            self::PROCESSING => 'processmiddle',
            self::COMPLETED => 'alreadycomplete',
            self::FAILED => 'fail',
        };
    }

    /**
     * checkwhetherforsuccessstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * fromstringcreateenum.
     */
    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? self::FAILED;
    }
}
