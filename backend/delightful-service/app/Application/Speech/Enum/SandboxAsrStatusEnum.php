<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * 沙箱 ASR taskstatus枚举.
 *
 * 【asuse域】outside部system - 沙箱audiomergeservice
 * 【use途】table示沙箱middleaudiomergetaskexecutestatus
 * 【use场景】
 * - call沙箱 finishTask interfaceround询statusjudge
 * - judgeaudiominuteslicemergewhethercomplete
 *
 * 【andother枚举区别】
 * - AsrRecordingStatusEnum: front端录音实o clockstatus(录音交互layer)
 * - AsrTaskStatusEnum: inside部taskallprocessstatus(businessmanagelayer)
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus(基础设施layer)✓ current
 *
 * 【statusstream转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // etc待middle:taskalreadysubmit,etc待沙箱process
    case RUNNING = 'running';           // 运linemiddle:沙箱justinprocessaudiominuteslice
    case FINALIZING = 'finalizing';     // justinexecutefinalmerge:沙箱justinmergeaudioandprocess笔记file
    case COMPLETED = 'completed';       // taskcomplete(V2 newformat):audiomergeandfileprocessall部complete
    case FINISHED = 'finished';         // taskcomplete(tobackcompatibleoldformat):保留useatcompatibleoldversion沙箱
    case ERROR = 'error';               // error:沙箱processfail

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
