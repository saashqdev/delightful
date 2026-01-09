<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * 沙箱 ASR taskstatus枚举.
 *
 * 【作use域】outside部系统 - 沙箱audiomergeservice
 * 【use途】table示沙箱middleaudiomergetask的executestatus
 * 【use场景】
 * - call沙箱 finishTask interface的round询status判断
 * - 判断audiominuteslicemergewhethercomplete
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: front端录音实o clockstatus（录音交互layer）
 * - AsrTaskStatusEnum: inside部taskallprocessstatus（业务管理layer）
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus（基础设施layer）✓ current
 *
 * 【statusstream转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // etc待middle：task已submit，etc待沙箱process
    case RUNNING = 'running';           // 运linemiddle：沙箱正inprocessaudiominuteslice
    case FINALIZING = 'finalizing';     // 正inexecutefinalmerge：沙箱正inmergeaudio并process笔记file
    case COMPLETED = 'completed';       // taskcomplete（V2 新format）：audiomerge和fileprocessall部complete
    case FINISHED = 'finished';         // taskcomplete（tobackcompatible旧format）：保留useatcompatible旧version沙箱
    case ERROR = 'error';               // error：沙箱processfail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => 'etc待middle',
            self::RUNNING => '运linemiddle',
            self::FINALIZING => '正inmerge',
            self::COMPLETED => '已complete',
            self::FINISHED => '已complete（旧）',
            self::ERROR => 'error',
        };
    }

    /**
     * whether为completestatus（contain新旧两typeformat）.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED || $this === self::FINISHED;
    }

    /**
     * whether为errorstatus.
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * whether为middlebetweenstatus（needcontinueround询）.
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
