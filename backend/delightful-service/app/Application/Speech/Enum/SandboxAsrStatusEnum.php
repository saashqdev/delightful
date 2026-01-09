<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * sandbox ASR taskstatus枚举.
 *
 * 【asuse域】outside部system - sandboxaudiomergeservice
 * 【use途】table示sandboxmiddleaudiomergetaskexecutestatus
 * 【usescenario】
 * - callsandbox finishTask interfaceround询statusjudge
 * - judgeaudiominuteslicemergewhethercomplete
 *
 * 【andother枚举区别】
 * - AsrRecordingStatusEnum: front端recording实o clockstatus(recording交互layer)
 * - AsrTaskStatusEnum: inside部taskallprocessstatus(businessmanagelayer)
 * - SandboxAsrStatusEnum: sandboxmergetaskstatus(infrastructurelayer)✓ current
 *
 * 【statusstream转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // etc待middle:taskalreadysubmit,etc待sandboxprocess
    case RUNNING = 'running';           // 运linemiddle:sandboxjustinprocessaudiominuteslice
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
            self::RUNNING => '运linemiddle',
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
     * whetherformiddlebetweenstatus(needcontinueround询).
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::WAITING, self::RUNNING, self::FINALIZING => true,
            default => false,
        };
    }

    /**
     * fromstringsecuritycreate枚举.
     */
    public static function fromString(string $status): ?self
    {
        return self::tryFrom($status);
    }
}
