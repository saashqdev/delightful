<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * 沙箱 ASR taskstatus枚举.
 *
 * 【作用域】外部系统 - 沙箱audiomergeservice
 * 【用途】table示沙箱中audiomergetask的executestatus
 * 【use场景】
 * - call沙箱 finishTask interface的轮询status判断
 * - 判断audio分片merge是否complete
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: 前端录音实时status（录音交互层）
 * - AsrTaskStatusEnum: 内部task全processstatus（业务管理层）
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus（基础设施层）✓ current
 *
 * 【statusstream转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // 等待中：task已submit，等待沙箱process
    case RUNNING = 'running';           // 运行中：沙箱正在processaudio分片
    case FINALIZING = 'finalizing';     // 正在executefinalmerge：沙箱正在mergeaudio并process笔记file
    case COMPLETED = 'completed';       // taskcomplete（V2 新format）：audiomerge和fileprocess全部complete
    case FINISHED = 'finished';         // taskcomplete（向后compatible旧format）：保留用于compatible旧version沙箱
    case ERROR = 'error';               // error：沙箱processfail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => '等待中',
            self::RUNNING => '运行中',
            self::FINALIZING => '正在merge',
            self::COMPLETED => '已complete',
            self::FINISHED => '已complete（旧）',
            self::ERROR => 'error',
        };
    }

    /**
     * 是否为completestatus（contain新旧两种format）.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED || $this === self::FINISHED;
    }

    /**
     * 是否为errorstatus.
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * 是否为中间status（needcontinue轮询）.
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::WAITING, self::RUNNING, self::FINALIZING => true,
            default => false,
        };
    }

    /**
     * 从string安全create枚举.
     */
    public static function fromString(string $status): ?self
    {
        return self::tryFrom($status);
    }
}
