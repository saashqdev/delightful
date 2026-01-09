<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * 沙箱 ASR taskstatus枚举.
 *
 * 【作use域】外部系统 - 沙箱audiomergeservice
 * 【use途】table示沙箱中audiomergetask的executestatus
 * 【use场景】
 * - call沙箱 finishTask interface的轮询status判断
 * - 判断audio分片mergewhethercomplete
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: 前端录音实时status（录音交互层）
 * - AsrTaskStatusEnum: 内部taskallprocessstatus（业务管理层）
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus（基础设施层）✓ current
 *
 * 【statusstream转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // etc待中：task已submit，etc待沙箱process
    case RUNNING = 'running';           // 运行中：沙箱正inprocessaudio分片
    case FINALIZING = 'finalizing';     // 正inexecutefinalmerge：沙箱正inmergeaudio并process笔记file
    case COMPLETED = 'completed';       // taskcomplete（V2 新format）：audiomerge和fileprocessall部complete
    case FINISHED = 'finished';         // taskcomplete（to后compatible旧format）：保留useatcompatible旧version沙箱
    case ERROR = 'error';               // error：沙箱processfail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => 'etc待中',
            self::RUNNING => '运行中',
            self::FINALIZING => '正inmerge',
            self::COMPLETED => '已complete',
            self::FINISHED => '已complete（旧）',
            self::ERROR => 'error',
        };
    }

    /**
     * whether为completestatus（contain新旧两种format）.
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
     * whether为中间status（needcontinue轮询）.
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
