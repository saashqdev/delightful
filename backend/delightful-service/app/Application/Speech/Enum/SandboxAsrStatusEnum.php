<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * sandbox ASR taskstatusenum.
 *
 * 【asuse域】outside部system - sandboxaudiomergeservice
 * 【use途】tableshowsandboxmiddleaudiomergetaskexecutestatus
 * 【usescenario】
 * - callsandbox finishTask interfaceroundquerystatusjudge
 * - judgeaudiominuteslicemergewhethercomplete
 *
 * 【andotherenumdifference】
 * - AsrRecordingStatusEnum: frontclientrecordingactualo clockstatus(recordinginteractionlayer)
 * - AsrTaskStatusEnum: inside部taskallprocessstatus(businessmanagelayer)
 * - SandboxAsrStatusEnum: sandboxmergetaskstatus(infrastructurelayer)✓ current
 *
 * 【statusstreamtransfer】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // etc待middle:taskalreadysubmit,etc待sandboxprocess
    case RUNNING = 'running';           // runlinemiddle:sandboxjustinprocessaudiominuteslice
    case FINALIZING = 'finalizing';     // justinexecutefinalmerge:sandboxjustinmergeaudioandprocessnotefile
    case COMPLETED = 'completed';       // taskcomplete(V2 newformat):audiomergeandfileprocessall部complete
    case FINISHED = 'finished';         // taskcomplete(tobackcompatibleoldformat):retainuseatcompatibleoldversionsandbox
    case ERROR = 'error';               // error:sandboxprocessfail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => 'etc待middle',
            self::RUNNING => 'runlinemiddle',
            self::FINALIZING => 'justinmerge',
            self::COMPLETED => 'alreadycomplete',
            self::FINISHED => 'alreadycomplete(old)',
            self::ERROR => 'error',
        };
    }

    /**
     * whetherforcompletestatus(containage两typeformat).
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED || $this === self::FINISHED;
    }

    /**
     * whetherforerrorstatus.
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * whetherformiddlebetweenstatus(needcontinueroundquery).
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::WAITING, self::RUNNING, self::FINALIZING => true,
            default => false,
        };
    }

    /**
     * fromstringsecuritycreateenum.
     */
    public static function fromString(string $status): ?self
    {
        return self::tryFrom($status);
    }
}
