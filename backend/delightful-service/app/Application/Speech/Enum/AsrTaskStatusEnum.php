<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR taskstatusenum(insidedepartmentbusinessprocess).
 *
 * 【asusedomain】insidedepartmentsystem - delightful-service businesslayer
 * 【useroute】tableshow ASR recordingsummarytaskalllifeperiodstatus
 * 【usescenario】
 * - taskstatuspersistence(Redis/database)
 * - businessprocesscontrolandpoweretcpropertyjudge
 * - organizebodytaskstatustrace(recording → merge → generatetitle → sendmessage)
 *
 * 【andotherenumdifference】
 * - AsrRecordingStatusEnum: frontclientrecordingactualo clockstatus(recordinginteractionlayer)
 * - AsrTaskStatusEnum: insidedepartmenttaskallprocessstatus(businessmanagelayer)✓ current
 * - SandboxAsrStatusEnum: sandboxmergetaskstatus(infrastructurelayer)
 *
 * 【statusstreamtransfer】created → processing → completed | failed
 */
enum AsrTaskStatusEnum: string
{
    case CREATED = 'created';              // alreadycreate:taskinitializecomplete,etcpendingprocess
    case PROCESSING = 'processing';        // processmiddle:justinexecuterecording,mergeorsummary
    case COMPLETED = 'completed';          // alreadycomplete:organize ASR processalldepartmentcomplete(includemessagesend)
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
